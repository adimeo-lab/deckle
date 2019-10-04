<?php


namespace Adimeo\Deckle\Command;


use Adimeo\Deckle\Exception\DeckleException;
use Adimeo\Deckle\Service\Config\ConfigManager;
use Adimeo\Deckle\Service\Placeholder\PlaceholderInterface;
use Adimeo\Deckle\Service\Placeholder\PlaceholdersManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
     * AbstractDeckleCommand constructor.
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager, PlaceholdersManager $placeholdersManager)
    {
        $this->configManager = $configManager;
        $this->placeholdersManager = $placeholdersManager;


        $this->loadEnvironment();

        parent::__construct(null);
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        return parent::run($input, $output); // TODO: Change the autogenerated stub
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
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
        if ($this->output->isVerbose()) {
            $this > $this->output->writeln("Importing configuration file <comment>deckle.yml</comment>");
        }
        $conf = $this->configManager->load('deckle.yml');

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

}
