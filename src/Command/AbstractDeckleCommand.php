<?php


namespace Adimeo\Deckle\Command;


use Adimeo\Deckle\Command\Deckle\Bootstrap;
use Adimeo\Deckle\Command\Deckle\Install;
use Adimeo\Deckle\Command\Deckle\PushDockerConfig;
use Adimeo\Deckle\Exception\DeckleException;
use Adimeo\Deckle\Service\Config\ConfigManager;
use Adimeo\Deckle\Service\Placeholder\PlaceholderInterface;
use Adimeo\Deckle\Service\Placeholder\PlaceholdersManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class AbstractDeckleCommand
 * @package Adimeo\Deckle\Command
 */
abstract class AbstractDeckleCommand extends Command
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var string */
    protected $env;

    /** @var array */
    protected $projectConfig = [];
    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var OutputInterface
     */
    protected $output;

    /** @var array */
    protected $currentlyResolving = [];

    /** @var PlaceholdersManager */
    protected $placeholdersManager;
    /**
     * @var string
     */
    protected $lastSshCommandOutput;

    /**
     * AbstractDeckleCommand constructor.
     * @param ConfigManager $configManager
     * @param PlaceholdersManager $placeholdersManager
     * @throws DeckleException
     */
    public function __construct(ConfigManager $configManager, PlaceholdersManager $placeholdersManager)
    {
        $this->configManager = $configManager;
        $this->placeholdersManager = $placeholdersManager;

        $this->loadEnvironment();

        parent::__construct(null);
        $this->addOption('config-file', 'c', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Extra configuration file', []);
    }


    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL|OutputInterface::VERBOSITY_VERBOSE);

        if(!isset($this->projectConfig['project']['name'])) $this->loadProjectConfig();
    }

    /**
     * @return bool
     */
    protected function loadEnvironment(): bool
    {
        if (!file_exists('./.deckle.env')) {
            // throw new DeckleException('.deckle.env file is missing. Did you bootstrap your project?');
            // default to dev
            $environment = 'dev';
        } else {
            $environment = trim(file_get_contents('./.deckle.env'));
        }

        if (!in_array($environment, ['dev', 'prod'])) {
            throw new DeckleException(['Invalid environment "%s" set in .deckle.env', $environment]);
        }

        $this->env = $environment;

        return $environment;
    }

    /**
     *
     */
    public function loadProjectConfig()
    {
        $configFiles = [
            '~/.deckle/deckle.yml',
            './deckle.yml',
            './deckle.local.yml'
        ];


        if ($extraConfigurationFiles = $this->input->getOption('config-file')) {
            $configFiles = array_merge($configFiles, $extraConfigurationFiles);
        }

        if (!is_dir('./deckle')) {
            if (($this instanceof Bootstrap) || $this instanceof Install) {
                return;
            }
        }
        if ($this->output->isVerbose()) {
            $this->output->writeln("Importing configuration files");
        }

        $conf = [];
        $loadedFiles = 0;
        foreach ($configFiles as $configFile) {
            if (file_exists($this->expandTilde($configFile))) {
                if ($this->output->isVerbose()) {
                    $this->output->writeln("\tLoading configuration file <comment>" . $configFile . "</comment>");
                }
                $loadedConf = $this->configManager->load($this->expandTilde($configFile));

                $conf = $this->configManager->merge($conf, $loadedConf);
                $loadedFiles++;
            } else {
                if ($this->output->isVeryVerbose()) {
                    $this->output->writeln('<error>Missing configuration file "' . $configFile . '"</error>');
                }
            }
        }

        if (!$loadedFiles) {
            $this->output->writeln('<warning>No deckle config file found</warning>');
            exit;
        }

        if (isset($conf['project']['extra_' . $this->getEnv() . '_configuration'])) {
            $extraConfigurationFiles = $conf['project']['extra_' . $this->getEnv() . '_configuration'];
            foreach ($extraConfigurationFiles as $file) {
                if ($this->output->isVerbose()) {
                    $this > $this->output->writeln("\tImporting extra configuration file <comment>" . $file . "</comment>");
                }
                $extra = $this->configManager->load($file);
                $conf = $this->configManager->merge($conf, $extra);
            }
        }

        // add default values
        if (!isset($conf['project']['name'])) {
            $conf['project'] = [];
            $conf['project']['name'] = strtolower(basename(getcwd()));
        }

        if (!isset($conf['docker']['host'])) {
            $conf['docker'] = [];
            $conf['docker']['host'] = getenv('DOCKER_HOST') ?? 'localhost:4243';
        }

        $this->projectConfig = $conf;

    }

    /**
     * @param $path
     * @return mixed
     */
    function expandTilde($path)
    {
        if (extension_loaded('posix') && strpos($path, '~') !== false) {
            $info = posix_getpwuid(posix_getuid());
            $path = str_replace('~', $info['dir'], $path);
        }
        return $path;
    }

    /**
     * @return string
     */
    public function getEnv(): string
    {
        return $this->env;
    }

    /**
     * @param string $env
     */
    public function setEnv(string $env): void
    {
        $this->env = $env;
    }

    /**
     * @param array $config
     */
    public function setProjectConfig(array $config)
    {
        $this->projectConfig = $config;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function getConfigDirective($key, $default = null)
    {
        $config = $this->getProjectConfig();
        codecept_debug($config);
        do {
            $path = explode('.', $key);
            codecept_debug($path);
            if (count($path) == 1) {
                return $config[$path[0]] ?? $default;
            } else {
                $config = $config[$path[0]];
                $key = $path[1];
            }
        } while (true);
    }

    public function getEnvVariables(): array
    {
        return $this->getProjectConfig()['env'];
    }

    public function getEnvVariable($var, $default = null)
    {
        return $this->processPlaceholders($this->getEnvVariables()[$var] ?? $default);


    }

    public function resolvePlaceholderValue(PlaceholderInterface $placeholder): string
    {

        if (isset($this->currentlyResolving[$placeholder->getRaw()])) {
            throw new DeckleException(['Circular resolution detected while resolving "%s"', $placeholder->getRaw()]);
        }
        $this->currentlyResolving[$placeholder->getRaw()] = true;

        switch ($placeholder->getType()) {
            case 'env':
                $value = $this->getEnvVariable($placeholder->getParams()[0]);
                break;

            case 'conf':
                $value = $this->getConfigDirective($placeholder->getParams()[0]);
                break;

            case 'ask':
                $helper = $this->getHelper('question');
                $question = new Question($placeholder->getParams()[0],
                    $placeholder->getParams()['default'] ?? null);
                $value = $helper->ask($this->input, $this->output, $question);
                break;

            default:
                throw new DeckleException([
                    'Unknown placeholder type "%s" in placeholder "%s"',
                    $placeholder->getType(),
                    $placeholder->getRaw()
                ]);
        }
        if (!$value) {
            if (isset($placeholder->getParams()['default'])) {
                unset($this->currentlyResolving[$placeholder->getRaw()]);
                return $placeholder->getParams()['default'];
            }
        } else {
            unset($this->currentlyResolving[$placeholder->getRaw()]);
            return $value;
        }


        throw new DeckleException(['Unable to resolve value for placeholder "%s"', $placeholder->getRaw()]);
    }

    protected function processPlaceholders($param)
    {
        $placeholders = $this->placeholdersManager->extractPlaceholders($param);
        foreach ($placeholders as $placeholder) {
            $param = $this->placeholdersManager->substitutePlaceholder($param, $placeholder,
                $this->resolvePlaceholderValue($placeholder));
        }
    }

    /**
     * @return array
     */
    public function getProjectConfig(): array
    {
        if (!$this->projectConfig) {
            $this->loadProjectConfig();
        }
        return $this->projectConfig;
    }

    protected function runCommandInContainer($command, $args = [], $workingDirectory = '~', $container = null)
    {

        $containerId = is_null($container) ? $this->getAppContainerId() : $this->getContainerId($container);

        $cmd = 'docker exec -ti ' . $containerId . ' bash -c "cd ' . escapeshellarg($workingDirectory) . ';' . escapeshellcmd($command);
        foreach ($args as &$arg) {
            $arg = escapeshellarg($arg);
        }
        $cmd .= ' ' . implode(' ', $args) . '"';

        if($this->output->isVeryVerbose()) {
            $this->output->writeln('Running <comment>' . $cmd . '</comment> on Docker remote host <comment>' . $this->projectConfig['docker']['host'] . '</comment>');
        }
        passthru($cmd);
    }

    protected function ssh($command, $workingDirectory = '~', $host = null, $user = null)
    {
        $user = $user ?? $this->projectConfig['vm']['user'];
        $host = $host ?? $this->projectConfig['vm']['host'];



        if($workingDirectory != '~') {
            $command = 'cd ' . $workingDirectory . '; ' . $command;
        }
        $command = escapeshellarg($command);
        $sshCommand = 'ssh ' . $user . '@' . $host . ' ' . $command;
        if($this->output->isVeryVerbose()) {
            $this->output->writeln('About to execute SSH command: <comment>' . $sshCommand . '</comment>');
        }
        $this->lastSshCommandOutput = exec($sshCommand, $output, $return);

        return $return;
    }

    protected function scp($source, $target, $host = null, $user = null)
    {
        $user = $user ?? $this->projectConfig['vm']['user'];
        $host = $host ?? $this->projectConfig['vm']['host'];

        $args = is_dir($source) ? '-r' : '';

        $scpCommand = 'scp ' . $args . ' ' . $source . ' ' . $user . '@' . $host . '":' . $target . '"';

        if($this->output->isVeryVerbose()) {
            $this->output->writeln('About to execute SCP command: <comment>' . $scpCommand . '</comment>');
        }

        $this->lastSshCommandOutput = exec($scpCommand, $output, $return);

        return $return;

    }


    protected function getContainerId(string $containerName)
    {

        $ch = curl_init($this->projectConfig['docker']['host'] . '/containers/json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $containers = curl_exec($ch);

        if ($containers) {
            $containers = json_decode($containers, true);
        } else {
            throw new DeckleException(['Docker does not seem to be running on %s', $this->projectConfig['docker']['host']]);
        }


        foreach ($containers as $container) {
            foreach ($container['Names'] as $name) {
                if ($containerName == trim($name, '/') || $containerName . '_1' == trim($name, '/')) {
                    return $container['Id'];
                }
            }
        }
    }

    protected function getAppContainerId()
    {
        $appContainer = $this->projectConfig['app']['container'];
        return $this->getContainerId($appContainer);
    }

    /**
     * @return string
     */
    public function getLastSshCommandOutput(): string
    {
        return $this->lastSshCommandOutput;
    }

    /**
     * @param string $lastSshCommandOutput
     * @return AbstractDeckleCommand
     */
    public function setLastSshCommandOutput(string $lastSshCommandOutput): AbstractDeckleCommand
    {
        $this->lastSshCommandOutput = $lastSshCommandOutput;
        return $this;
    }

}
