<?php


namespace Adimeo\Deckle\Command;


use Adimeo\Deckle\Command\Deckle\Install;
use Adimeo\Deckle\Container\ContainerAwareInterface;
use Adimeo\Deckle\Container\ContainerAwareTrait;
use Adimeo\Deckle\Exception\DeckleException;
use Adimeo\Deckle\Service\Config\ConfigService;
use Adimeo\Deckle\Service\Config\DeckleConfig;
use Adimeo\Deckle\Service\Docker\DockerTrait;
use Adimeo\Deckle\Service\Filesystem\FilesystemTrait;
use Adimeo\Deckle\Service\Git\GitTrait;
use Adimeo\Deckle\Service\Placeholder\PlaceholderInterface;
use Adimeo\Deckle\Service\Placeholder\PlaceholdersService;
use Adimeo\Deckle\Service\Shell\Script\Location\DeckleMachine;
use Adimeo\Deckle\Service\Shell\ShellTrait;
use Adimeo\Deckle\Service\Templates\TemplatesTrait;
use Adimeo\Deckle\Service\Vm\VmTrait;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AbstractDeckleCommand
 * @package Adimeo\Deckle\Command
 */
abstract class AbstractDeckleCommand extends Command implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    /** @var ConfigService */
    protected $configService;

    /** @var DeckleConfig */
    protected $config;
    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var SymfonyStyle
     */
    protected $output;

    /** @var array */
    protected $currentlyResolving = [];

    /** @var PlaceholdersService */
    protected $placeholdersService;

    use ShellTrait;
    use VmTrait;
    use FilesystemTrait;
    use DockerTrait;
    use GitTrait;
    use TemplatesTrait;

    /**
     * AbstractDeckleCommand constructor.
     * @param ServicesFactory $servicesFactory
     * @param ConfigService $configService
     * @param PlaceholdersService $placeholdersService
     */
    public function __construct(
        ServicesFactory $servicesFactory,
        ConfigService $configService,
        PlaceholdersService $placeholdersService
    ) {

        $this->configService = $configService;
        $this->placeholdersService = $placeholdersService;
        $this->setContainer($servicesFactory);
        $this->config = $servicesFactory->get(DeckleConfig::class);

        parent::__construct(null);

    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws DeckleException
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = new SymfonyStyle($input, $output);

        if (!$this instanceof ProjectIndependantCommandInterface) {
            if (!is_dir('./deckle')) {
                $this->error('No "./deckle" folder found. You may need to bootstrap your project.');
            }
        }

        $this->loadConfig();
    }

    /**
     *
     */
    public function loadConfig()
    {
        $configFiles = [
            Install::DECKLE_HOME . '/deckle.conf.yml',
            Install::DECKLE_HOME . '/deckle.local.yml',
            './deckle/deckle.yml',
            './deckle.local.yml'
        ];

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln("<info>Loading configuration files...</info>");
        }

        $conf = [];
        $loadedFiles = 0;

        foreach ($configFiles as $configFile) {
            if (file_exists($this->fs()->expandTilde($configFile))) {
                if ($this->output->isVeryVerbose()) {
                    $this->output->writeln("Loading configuration file <comment>" . $configFile . "</comment>");
                }
                $loadedConf = $this->configService->load($this->fs()->expandTilde($configFile));

                $conf = $this->configService->merge($conf, $loadedConf);
                $loadedFiles++;
            } else {
                if ($this->output->isVeryVerbose()) {
                    $this->output->note('Missing configuration file <info>' . $configFile . '</info>');
                }
            }
        }

        if (!$this instanceof ProjectIndependantCommandInterface) {

            if (!isset($conf['project']['name'])) {
                $this->error('Missing project name in configuration!');
            }
        }


        try {
            $this->config->hydrate($conf);
        } catch (DeckleException $e) {
            $this->error($e->getMessage());
        }

    }


    /**
     * @param DeckleConfig $config
     */
    public function setConfig(DeckleConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param $key
     * @param null $default
     * @return DeckleConfig|null
     */
    public function getConfig($key = null, $default = null)
    {
        if (!$this->hasConfig()) {
            $this->loadConfig();
        }

        if (is_null($key)) {
            return $this->config;
        }
        return $this->config->get($key, $default);
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
                $value = $this->getConfig($placeholder->getParams()[0]);
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

    /**
     * @return SymfonyStyle
     */
    protected function output()
    {
        return $this->output;
    }

    /**
     * @return InputInterface
     */
    protected function input()
    {
        return $this->input;
    }

    /**
     * @param $param
     * @throws DeckleException
     */
    protected function processPlaceholders($param)
    {
        $placeholders = $this->placeholdersService->extractPlaceholders($param);
        foreach ($placeholders as $placeholder) {
            $param = $this->placeholdersService->substitutePlaceholder($param, $placeholder,
                $this->resolvePlaceholderValue($placeholder));
        }
    }

    /**
     * @return PlaceholdersService
     */
    public function getPlaceholdersService(): PlaceholdersService
    {
        return $this->placeholdersService;
    }

    /**
     * @param PlaceholdersService $placeholdersService
     * @return AbstractDeckleCommand
     */
    public function setPlaceholdersService(PlaceholdersService $placeholdersService): AbstractDeckleCommand
    {
        $this->placeholdersService = $placeholdersService;
        return $this;
    }

    /**
     * @param string $message
     * @param array $vars
     * @param int $returnCode
     * @throws DeckleException
     */
    protected function error(string $message, array $vars = [], $returnCode = -1)
    {
        $exceptionParams = array_merge([$message], $vars);
        $messageParams = $vars;

        array_walk($messageParams, function (&$param) {
            $param = '<info>' . $param . '</info>';
        });
        $displayedMessage = vsprintf($message, $vars);

        $this->output()->error($displayedMessage);

        if ($this->output->isVeryVerbose()) {
            $e = new DeckleException($exceptionParams);
            throw $e;
        }

        exit($returnCode);
    }

    /**
     * @param $message
     * @param array $vars
     */
    protected function halt($message, array $vars = [])
    {
        $messageParams = $vars;

        array_walk($messageParams, function (&$param) {
            $param = '<info>' . $param . '</info>';
        });
        $message = implode(PHP_EOL, (array)$message);
        $displayedMessage = vsprintf($message, $vars);

        $this->output->warning($displayedMessage);

        exit(0);
    }

    /**
     * @param $template
     * @param bool $ignoreMissing Silently ignore unresolved placeholders
     * @param array $ignoreExceptions Raw placeholders that should not be ignored
     * @return mixed
     * @throws DeckleException
     */
    protected function processTemplate($template, $ignoreMissing = false, $ignoreExceptions = [])
    {
        $manager = $this->getPlaceholdersService();
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

    /**
     * @param string $templateFile
     * @param string $target
     * @param bool $ignoreMissing
     * @param array $ignoreExceptions
     * @throws DeckleException
     */
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

    /**
     * @return string
     */
    protected function getVersion()
    {
        $version = $this->getApplication()->getVersion();
        if (strpos($version, 'package')) {

            if (is_dir('.git')) {
                $head = file_get_contents('.git//HEAD');
                $branch = rtrim(preg_replace("/(.*?\/){2}/", '', $head));
                $version = $branch . '-' . exec('git rev-parse --short HEAD');
            } else {
                $version = 'unknown';
            }
        }

        return $version;
    }


    /**
     * @param $question
     * @param bool $default
     * @return bool
     */
    protected function confirm($question, bool $default = false): bool
    {
        $helper = $this->getHelper('question');
        $defaultChoice = ($default) ? '[Yn]' : '[yN]';
        $question = new ConfirmationQuestion('<question>' . $question . ' ' . $defaultChoice . '</question> ',
            $default);

        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * @return bool
     */
    public function hasConfig()
    {
        return (bool)$this->config;
    }

    /**
     * @return DeckleMachine
     */
    protected function getDeckleMachineLocation()
    {
        $machine = new DeckleMachine();
        $this->sh()->completeDeckleMachineLocation($machine);

        return $machine;
    }

}
