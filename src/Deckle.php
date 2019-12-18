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
use Adimeo\Deckle\Command\Deckle\InstallMacOs;
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
use Adimeo\Deckle\Command\Templates\ListTemplates;
use Adimeo\Deckle\Command\Templates\Update;
use Adimeo\Deckle\Command\Vagrant\Vagrant;
use Adimeo\Deckle\Command\Vm\AddKnownHost;
use Adimeo\Deckle\Command\Vm\Ip;
use Adimeo\Deckle\Command\Vm\Ssh;
use Adimeo\Deckle\Command\Vm\SshCopyId;
use ErrorException;
use ObjectivePHP\DocuMentor\ReflectionFile;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class Deckle extends Application
{

    /** @var ServicesFactory */
    protected $container;

    /** @var InputInterface */
    protected static $input;

    /** @var SymfonyStyle */
    protected static $output;

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

        $this->container = new ServicesFactory();


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

        $output = $output ?: new ConsoleOutput();
        $output->getFormatter()->setStyle(
            'error',
            new OutputFormatterStyle('red')
        );

        $output->getFormatter()->setStyle(
            'question',
            new OutputFormatterStyle('white', 'cyan')
        );

        self::$input = $input ?? new ArrayInput([]);
        self::$output = new SymfonyStyle(self::$input,$output);

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
            InstallMacOs::class,
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
            $this->add($this->container->get($command));
        }
    }

    protected function registerLocalCommands()
    {
        $finder = new Finder();
        if(is_dir('./deckle/commands')) {
            foreach($finder->in('./deckle/commands')->name('*Command.php') as $commandFile) {
                try {
                    require $commandFile;
                    $command = new ReflectionFile($commandFile);
                    $this->add($this->container->get($command->getName()));
                } catch (\Throwable $e) {
                    $output = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());
                    $output->warning($e);
                }
            }
        }
    }

    public static function input() : InputInterface
    {
        return self::$input ?? new ArrayInput([]);
    }

    public static function output() : OutputInterface
    {
        return self::$output ?? new SymfonyStyle(self::input(), new ConsoleOutput());
    }

}
