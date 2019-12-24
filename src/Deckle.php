<?php


namespace Adimeo\Deckle;


use Adimeo\Deckle\Command\Apps\Apps;
use Adimeo\Deckle\Command\Deckle\Bootstrap;
use Adimeo\Deckle\Command\Deckle\Clear;
use Adimeo\Deckle\Command\Deckle\Config;
use Adimeo\Deckle\Command\Deckle\DbImport;
use Adimeo\Deckle\Command\Deckle\Down;
use Adimeo\Deckle\Command\Deckle\Init;
use Adimeo\Deckle\Command\Deckle\Install;
use Adimeo\Deckle\Command\Deckle\Installer\LinuxInstaller;
use Adimeo\Deckle\Command\Deckle\Installer\MacOsInstaller;
use Adimeo\Deckle\Command\Deckle\PushDockerConfig;
use Adimeo\Deckle\Command\Deckle\Selfupdate;
use Adimeo\Deckle\Command\Deckle\Status;
use Adimeo\Deckle\Command\Deckle\Up;
use Adimeo\Deckle\Command\Docker\Compose;
use Adimeo\Deckle\Command\Docker\Docker;
use Adimeo\Deckle\Command\Docker\Shell;
use Adimeo\Deckle\Command\Drupal8\Drupal;
use Adimeo\Deckle\Command\Drupal8\Drupal8ImportReferenceDb;
use Adimeo\Deckle\Command\Drupal8\Drupal8Init;
use Adimeo\Deckle\Command\Drupal8\Drush;
use Adimeo\Deckle\Command\Drupal8\GenerateLocalSettings;
use Adimeo\Deckle\Command\Mutagen\Monitor;
use Adimeo\Deckle\Command\Mutagen\Sync;
use Adimeo\Deckle\Command\Php\Cli;
use Adimeo\Deckle\Command\Php\Composer;
use Adimeo\Deckle\Command\Symfony\SfInit;
use Adimeo\Deckle\Command\Templates\ListTemplates;
use Adimeo\Deckle\Command\Templates\Update;
use Adimeo\Deckle\Command\Vagrant\Vagrant;
use Adimeo\Deckle\Command\Vm\AddKnownHost;
use Adimeo\Deckle\Command\Vm\Ip;
use Adimeo\Deckle\Command\Vm\Ssh;
use Adimeo\Deckle\Command\Vm\SshCopyId;
use Adimeo\Deckle\Service\Config\DeckleConfig;
use ErrorException;
use ObjectivePHP\DocuMentor\ReflectionFile;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class Deckle extends Application
{

    /** @var ServicesFactory */
    static protected $container;

    /** @var InputInterface */
    protected static $input;

    /** @var SymfonyStyle */
    protected static $output;

    /**
     * @var ConsoleOutput
     */
    protected static $consoleOutput;

    /**
     * @override
     * @param string $name
     * @param string $version
     */
    public function __construct($name = 'Deckle', $version = '@package_version@')
    {
        // convert errors to exceptions
        set_error_handler(
            function ($code, $message, $file, $line) {
                if (error_reporting() & $code) {
                    throw new ErrorException($message, 0, $code, $file, $line);
                }
                // @codeCoverageIgnoreStart
            }
        // @codeCoverageIgnoreEnd
        );

        self::$container = new ServicesFactory();

        self::$container->registerService(['id' => 'app', 'instance' => $this]);

        $this->registerNativeCommands();
        $this->registerLocalCommands();


        parent::__construct($name, $version);
    }


    /**
     * @override
     */
    public function __getLongVersion()
    {
        if (('@' . 'package_version@') !== $this->getVersion()) {
            return sprintf(
                '<info>%s</info> version <comment>%s</comment> build <comment>%s</comment>',
                $this->getName(),
                $this->getVersion(),
                '@git-commit@'
            );
        }

        return '<info>' . $this->getName() . '</info> (development version)';
    }

    /**
     * @override
     * @param InputInterface|null $input
     * @param OutputInterface|null $output
     * @return int
     * @throws \Exception
     */
    public function run(
        InputInterface $input = null,
        OutputInterface $output = null
    ) {

        $output = self::console();


        self::$input = $input ?? new ArrayInput([]);
        self::$output = new SymfonyStyle(self::$input, $output);

        return parent::run($input, $output);
    }

    protected function registerNativeCommands()
    {
        $commands = [
            // Deckle
            Bootstrap::class,
            Config::class,
            DbImport::class,
            Status::class,
            Init::class,
            Install::class,
            MacOsInstaller::class,
            LinuxInstaller::class,
            PushDockerConfig::class,
            ListTemplates::class,
            Update::class,
            Selfupdate::class,
            Up::class,
            Down::class,

            // Docker
            Docker::class,
            Compose::class,
            Shell::class,

            // Vagrant
            Vagrant::class,

            // Drupal8
            Drupal::class,
            Drupal8ImportReferenceDb::class,
            Drupal8Init::class,
            Drush::class,
            GenerateLocalSettings::class,

            // Symfony
            SfInit::class,

            // Mutagen
            Sync::class,
            Monitor::class,

            // Php
            Cli::class,
            Composer::class,

            // Vm
            Ip::class,
            Ssh::class,
            AddKnownHost::class,
            SshCopyId::class,
            Apps::class

        ];

        foreach ($commands as $command) {
            $this->add(self::$container->get($command));
        }
    }

    protected function registerLocalCommands()
    {
        $finder = new Finder();
        if (is_dir('./deckle/commands')) {
            foreach ($finder->in('./deckle/commands')->name('*Command.php') as $commandFile) {
                try {
                    require $commandFile;
                    $command = new ReflectionFile($commandFile);
                    $this->add($this->container->get($command->getName()));
                } catch (\Throwable $e) {
                    Deckle::error('Unable to load local command file "%s"', $commandFile, false, $e);
                }
            }
        }
    }

    public static function input(InputInterface $input = null): InputInterface
    {
        if($input) self::$input = $input;

        return self::$input ?? new ArrayInput([]);
    }


    /**
     * @return SymfonyStyle
     */
    public static function output(OutputInterface $output = null): OutputInterface
    {
        if($output) self::$output = $output;
        return self::$output ?? new SymfonyStyle(self::input(null), new ConsoleOutput());
    }

    static private function write($method, $message, $vars = [], $quit = false, \Throwable $e = null)
    {
        if (is_scalar($vars)) {
            $vars = [$vars];
        }

        if (is_object($vars) && method_exists($vars, '__toString')) {
            $vars = [(string)$vars];
        }

        if (!is_array($vars)) {
            self::error('Values for placeholders must be a scalar or an array');
        }

        if (is_array($message)) {
            $message = implode(PHP_EOL, $message);
        }

        if (!method_exists(self::output(), $method)) {
            $method = 'error';
            $message = 'Unknown output method "%s"' . PHP_EOL . 'Original message: ' . PHP_EOL . $message;
            array_unshift($vars, $method);
        }

        try {
            self::output()->$method(vsprintf($message, $vars));
        } catch (\ErrorException $e) {
            $backtrace = debug_backtrace();
            foreach ($backtrace as $trace) {
                break;
            }

            self::print('<error>Incomplete message (missing variables)</error> in "<info>%s</info>"', [$message]);
            if (isset($trace)) {
                self::print('<comment>This message was output from class %s </comment>', [$trace['class']]);
            }
        }
        if ($quit !== false && $quit !== 0 && isset($e) && self::output()->isVeryVerbose()) {
            self::print('A <info>%s</info> exception was thrown in <info>%s</info>:<info>%s</info>',
                [get_class($e), $e->getFile(), $e->getLine()]);
            self::output()->writeln($e->getMessage());
            self::output()->writeln($e->getTraceAsString());
        }

        if ($quit !== false) {
            exit((int)$quit);
        }
    }


    static public function print($message, $vars = [], $returnCode = false)
    {
        self::write('writeLn', $message, $vars, $returnCode);
    }

    static public function br()
    {
        self::print('');
    }

    static public function success($message, $vars = [], $returnCode = 0)
    {
        self::write('success', $message, $vars, $returnCode);
    }

    static public function warning($message, $vars = [], $returnCode = false)
    {
        self::write('warning', $message, $vars, $returnCode);
    }

    static public function error($message, $vars = [], $returnCode = false)
    {
        if ($returnCode instanceof \Throwable) {
            $e = $returnCode;
            $returnCode = $e->getCode();
        }

        self::write('error', $message, $vars, $e ?? null);

    }

    static public function halt($message, $vars = [], $returnCode = 1)
    {
        self::warning($message, $vars, $returnCode);
    }

    static public function note($message, $vars = [], $returnCode = false)
    {
        self::write('note', $message, $vars, $returnCode);
    }

    static public function isVerbose(): bool
    {
        return self::output()->isVerbose();
    }

    static public function isVeryVerbose(): bool
    {
        return self::output()->isVeryVerbose();
    }

    static public function isQuiet(): bool
    {
        return self::output()->isQuiet();
    }

    static public function getVerbosity(): bool
    {
        return self::output()->getVerbosity();
    }

    static public function setVerbosity(int $verbosity): bool
    {
        return self::output()->setVerbosity($verbosity);
    }

    static public function confirm($question, $default = true)
    {
        return self::output()->confirm($question, $default);
    }

    static public function prompt($question, $default = '', $hidden = false)
    {
        $question = new Question($question, $default);
        $method = $hidden ? 'askHidden' : 'askQuestion';
        return self::output()->$method($question, $default);
    }

    static public function runCommand(string $commandName, array $params = [])
    {
        /** @var Deckle $application */
        $application = self::$container->get('app');
        $command = $application->find($commandName);

        if (!$command) {
            Deckle::error('Unknown command: %s', [$commandName]);
        }

        $command->setConfig(self::$container->get(DeckleConfig::class));

        $input = new ArrayInput($params);
        $input->setInteractive(isset($params['--no-interaction']) ? !$params['--no-interaction'] : true);
        $command->run($input, self::$output);
    }

    /**
     * @return ConsoleOutput
     */
    public static function console()
    {
        if (is_null(self::$consoleOutput)) {
            $output = new ConsoleOutput();
            $output->getFormatter()->setStyle(
                'error',
                new OutputFormatterStyle('red')
            );

            $output->getFormatter()->setStyle(
                'question',
                new OutputFormatterStyle('white', 'cyan')
            );
            self::$consoleOutput = $output;
        }

        return self::$consoleOutput;
    }

}
