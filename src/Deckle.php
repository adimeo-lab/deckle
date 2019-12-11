<?php


namespace Adimeo\Deckle;


use Adimeo\Deckle\Command\Apps\Apps;
use Adimeo\Deckle\Command\Deckle\Bootstrap;
use Adimeo\Deckle\Command\Deckle\Clear;
use Adimeo\Deckle\Command\Deckle\Config;
use Adimeo\Deckle\Command\Deckle\DbImport;
use Adimeo\Deckle\Command\Deckle\Info;
use Adimeo\Deckle\Command\Deckle\Init;
use Adimeo\Deckle\Command\Deckle\Install;
use Adimeo\Deckle\Command\Deckle\PushDockerConfig;
use Adimeo\Deckle\Command\Deckle\Selfupdate;
use Adimeo\Deckle\Command\Deckle\TemplatesList;
use Adimeo\Deckle\Command\Deckle\Update;
use Adimeo\Deckle\Command\Deckle\Version;
use Adimeo\Deckle\Command\Docker\Compose;
use Adimeo\Deckle\Command\Docker\Shell;
use Adimeo\Deckle\Command\Drupal8\Drupal;
use Adimeo\Deckle\Command\Drupal8\Drupal8ImportReferenceDb;
use Adimeo\Deckle\Command\Drupal8\Drupal8Init;
use Adimeo\Deckle\Command\Drupal8\Drush;
use Adimeo\Deckle\Command\Drupal8\GenerateLocalSettings;
use Adimeo\Deckle\Command\Mutagen\Mutagen;
use Adimeo\Deckle\Command\Php\Cli;
use Adimeo\Deckle\Command\Php\Composer;
use Adimeo\Deckle\Command\Vm\AddKnownHost;
use Adimeo\Deckle\Command\Vm\Ip;
use Adimeo\Deckle\Command\Vm\Ssh;
use Adimeo\Deckle\Command\Vm\SshCopyId;
use ErrorException;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Deckle extends Application
{
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

        $this->registerCommands();

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
            new OutputFormatterStyle('cyan')
        );

        return parent::run($input, $output);
    }

    protected function registerCommands()
    {
        $commands = [
            // Deckle
            Bootstrap::class,
            Clear::class,
            Config::class,
            DbImport::class,
            Info::class,
            Init::class,
            Install::class,
            PushDockerConfig::class,
            TemplatesList::class,
            Update::class,
            Selfupdate::class,
            Version::class,

            // Docker
            Compose::class,
            Shell::class,

            // Drupal8
            Drupal::class,
            Drupal8ImportReferenceDb::class,
            Drupal8Init::class,
            Drush::class,
            GenerateLocalSettings::class,

            // Mutagen
            Mutagen::class,

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
        $container = new ServicesFactory();
        foreach ($commands as $command) {
            $this->add($container->get($command));
        }
    }

}
