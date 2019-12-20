<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Service\Config\DeckleConfig;
use Adimeo\Deckle\Service\Misc\ArrayTool;
use Adimeo\Deckle\Service\Shell\Script\Location\AppContainer;
use Adimeo\Deckle\Service\Shell\Script\Location\DeckleMachine;
use Adimeo\Deckle\Service\Shell\Script\Location\LocalPath;
use Adimeo\Deckle\Service\Shell\Script\Location\SshHost;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Drupal8ImportReferenceDb extends AbstractDrupal8Command
{
    protected function configure()
    {
        $this->setName('drupal8:db:import')
            ->setHidden(true)
            ->setDescription('Import db from reference server to dev environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {

            // fetch config
            $config = $this->config;


            $command = 'vendor/bin/drush sql:dump | gzip > /tmp/deckle-dump.sql.gz';

            Deckle::print('Importing database from <info>' . $config['reference']['host'] . '</info>...');
            $section = $output->section();

            $section->overwrite('<comment>Generating dump on remote host...</comment>');
            $this->sh()->exec($command, new SshHost($config['reference']['host'], $config['reference']['path'], $config['reference']['user']));


            // fetch dump in vm
            $section->overwrite('<comment>Copying dump locally...</comment>');
            $localDump = tempnam(sys_get_temp_dir(), 'dump_');
            $this->sh()->scp(new SshHost($config['reference']['host'], '/tmp/deckle-dump.sql.gz', $config['reference']['user']),
                new LocalPath($localDump));

            $section->overwrite('<comment>Copying dump in app container...</comment>');
            $this->sh()->cp(new LocalPath($localDump), new AppContainer($config['app']['path'] . '/deckle-dump.sql.gz'))->isErrored();
            // import in app
            $section->overwrite('<comment>Running <info>drush sql:query</info> in container ...</comment>');
            $command = 'vendor/bin/drush sql:query --file ' . $config['app']['path'] . '/deckle-dump.sql.gz';
            $this->sh()->exec($command, new AppContainer($config['app']['path']));

            $section->overwrite('<info>Done!</info>');
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            // delete dump on reference
            $command = '[ -f deckle-dump.sql.gz ] && rm deckle-dump.sql.gz';
            Deckle::setVerbosity(OutputInterface::VERBOSITY_QUIET);
            $this->sh()->exec($command, new SshHost($config['reference']['host'], '~', $config['reference']['user']));
            if(isset($localDump) && file_exists($localDump)) unlink($localDump);
        }
    }

}
