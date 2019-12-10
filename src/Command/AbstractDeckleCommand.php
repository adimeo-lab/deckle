<?php


namespace Adimeo\Deckle\Command;


use Adimeo\Deckle\Command\Deckle\Install;
use Adimeo\Deckle\Config\DeckleConfig;
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

        parent::__construct(null);
        $this->addOption('config-file', 'c', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Extra configuration file', []);
    }


    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        if (!$this instanceof ProjectIndependantCommandInterface) {
            if (!is_dir('./deckle')) {
                $this->error('No "./deckle" folder found. You may need to bootstrap your project.');
            }

            if (!isset($this->projectConfig['project']['name'])) {
                $this->loadProjectConfig();
            }
        }
    }

    /**
     *
     */
    public function loadProjectConfig()
    {
        $configFiles = [
            Install::DECKLE_HOME . '/deckle.conf.yml',
            Install::DECKLE_HOME . '/deckle.local.yml',
            './deckle/deckle.yml',
            './deckle.local.yml'
        ];


        if ($extraConfigurationFiles = $this->input->getOption('config-file')) {
            $configFiles = array_merge($configFiles, $extraConfigurationFiles);
        }


        if ($this->output->isVerbose()) {
            $this->output->writeln("Importing configuration files");
        }

        $conf = [];
        $loadedFiles = 0;

        foreach ($configFiles as $configFile) {
            if (file_exists($this->expandTilde($configFile))) {
                if ($this->output->isVeryVerbose()) {
                    $this->output->writeln("Loading configuration file <comment>" . $configFile . "</comment>");
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
            $this->error('No deckle config file found!');
            exit;
        }

        /*
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
        */

        // add default values
        if (!isset($conf['project']['name'])) {
            $conf['project'] = [];
            $conf['project']['name'] = strtolower(basename(getcwd()));
        }

        if (!isset($conf['docker']['host'])) {
            if (!isset($conf['docker'])) {
                $conf['docker'] = [];
            }
            $conf['docker']['host'] = getenv('DOCKER_HOST') ?? 'localhost:4243';
        }

        try {
            $this->projectConfig = (new DeckleConfig($conf))->getConfigArray();
        } catch (DeckleException $e) {
            $this->error($e->getMessage());
        }

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
        do {
            $path = explode('.', $key);
            if (count($path) == 1) {
                return $config[$path[0]] ?? $default;
            } else {
                if (!isset($config[$path[0]])) {
                    return $default;
                }
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

    /**
     * @param PlaceholderInterface $placeholder
     * @param bool $silent Silently fails
     * @return string
     * @throws DeckleException
     */
    public function resolvePlaceholderValue(PlaceholderInterface $placeholder, $silent = false): string
    {

        if (isset($this->currentlyResolving[$placeholder->getRaw()])) {
            $this->error('Circular resolution detected while resolving "%s"', [$placeholder->getRaw()]);
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
                $this->error(
                    'Unknown placeholder type "%s" in placeholder "%s"',
                    [
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

        if (!$silent) {
            $this->error('Unable to resolve value for placeholder "%s"', [$placeholder->getRaw()]);
        }

        return '';
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
    public function getProjectConfig()
    {
        if (!$this->projectConfig) {
            $this->loadProjectConfig();
        }
        return $this->projectConfig;
    }

    protected function dockerExec($command, $args = [], $workingDirectory = '~', $container = null)
    {

        $containerId = is_null($container) ? $this->getAppContainerId() : $this->getContainerId($container);

        $cmd = 'docker exec -ti ' . $containerId . ' bash -c "cd ' . escapeshellarg($workingDirectory) . ';' . escapeshellcmd($command);
        foreach ($args as &$arg) {
            //  $arg = escapeshellarg($arg);
        }
        $cmd .= ' ' . implode(' ', $args) . '"';

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln('Running <comment>' . $cmd . '</comment> on Docker remote host <comment>' . $this->projectConfig['docker']['host'] . '</comment>');
        }

        passthru($cmd);
    }

    protected function ssh($command, $workingDirectory = '~', $host = null, $user = null)
    {
        $user = $user ?? $this->projectConfig['vm']['user'];
        $host = $host ?? $this->projectConfig['vm']['host'];

        if ($workingDirectory != '~') {
            $command = 'cd ' . $workingDirectory . '; ' . $command;
        }

        $command = escapeshellarg($command);
        $sshCommand = 'ssh ' . $user . '@' . $host . ' ' . $command;
        if ($this->output->isVeryVerbose()) {
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

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln('About to execute SCP command: <comment>' . $scpCommand . '</comment>');
        }

        $this->lastSshCommandOutput = exec($scpCommand, $output, $return);

        return $return;

    }


    protected function dockerStatus()
    {
        $ch = curl_init($this->projectConfig['docker']['host'] . '/_ping');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ping = curl_exec($ch);

        if ($ping) {
            if ($ping === 'OK') {
                return true;
            }
        }
        return false;


    }


    protected function getContainerId(string $containerName)
    {
        $ch = curl_init($this->projectConfig['docker']['host'] . '/containers/json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $containers = curl_exec($ch);

        if ($containers) {
            $containers = json_decode($containers, true);
        } else {
            $this->error('Docker does not seem to be running on %s', [$this->projectConfig['docker']['host']]);
        }


        foreach ($containers as $container) {
            foreach ($container['Names'] as $name) {
                if ($containerName == trim($name, '/')) {
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

    /**
     * @return PlaceholdersManager
     */
    public function getPlaceholdersManager(): PlaceholdersManager
    {
        return $this->placeholdersManager;
    }

    /**
     * @param PlaceholdersManager $placeholdersManager
     * @return AbstractDeckleCommand
     */
    public function setPlaceholdersManager(PlaceholdersManager $placeholdersManager): AbstractDeckleCommand
    {
        $this->placeholdersManager = $placeholdersManager;
        return $this;
    }

    protected function error(string $message, array $vars = [])
    {
        $exceptionParams = array_merge([$message], $vars);
        $messageParams = $vars;

        array_walk($messageParams, function (&$param) {
            $param = '<info>' . $param . '</info>';
        });
        $displayedMessage = vsprintf($message, $vars);

        $this->output->writeln('');
        $this->output->writeln('An error occurred:');
        $this->output->writeln("\t" . '<error>' . $displayedMessage . '</error>');
        $this->output->writeln('');
        if ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $e = new DeckleException($exceptionParams);
            throw $e;
        }

    }

    /**
     * @param $template
     * @param bool $ignoreMissing Silently ignore unresolved placeholders
     * @param array $ignoreExceptions Raw placeholders that should not be ignored
     * @return mixed
     */
    protected function processTemplate($template, $ignoreMissing = false, $ignoreExceptions = [])
    {
        $manager = $this->getPlaceHoldersManager();
        $placeholders = $manager->extractPlaceholders($template);

        /** @var PlaceholderInterface $placeholder */
        foreach ($placeholders as $placeholder) {
            $value = $this->resolvePlaceholderValue($placeholder, $ignoreMissing);

            if (!$value && $ignoreMissing && !in_array($placeholder->getRaw(), $ignoreExceptions)) {
                continue;
            }
            if ($this->output->isVeryVerbose()) {
                $this->output->writeln(sprintf('Replacing "<info>%s</info>" placeholder with resolved value "<info>%s</info>"',
                    $placeholder->getRaw(), $value));
            }
            $template = $manager->substitutePlaceholder($template, $placeholder, $value);
        }

        return $template;
    }

    protected function copyTemplateFile(
        string $templateFile,
        string $target,
        $ignoreMissing = true,
        $ignoreExceptions = []
    ) {
        if (!is_file($templateFile)) {
            $this->error('Template file "%s" does not exist', [$templateFile]);
        }
        if (!is_dir($target) && !is_dir(dirname($target))) {
            $this->error('Target directory for copying to "%s" does not exist', [$target]);
        }

        $template = file_get_contents($templateFile);
        file_put_contents($target, $this->processTemplate($template, $ignoreMissing));

    }

    protected function findVmAddressInHosts()
    {
        $entries = file('/etc/hosts');
        foreach ($entries as $entry) {
            if (strpos(trim($entry), '#') === 0) {
                continue;
            }
            [$ip, $names] = preg_split('/\s+/', $entry, 2);
            $names = preg_split('/\s+/', $names);
            if (in_array('deckle-vm', $names)) {
                return $ip;
            }
        }

        return null;
    }

    protected function getVersion()
    {
        $version = $this->getApplication()->getVersion();
        if (strpos($version, 'git')) {
            $version = 'development';
        }

        return $version;
    }

}
