<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Drupal8ImportReferenceDb extends AbstractDrupal8Command
{
    protected function configure()
    {
        $this->setName('drupal8:db:import')
            ->setDescription('Import db from reference server to dev environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {



            // dump
            $config = $this->projectConfig;
            $command = sprintf('mysqldump -h%s -u%s -p%s %s > %s-dump.sql',
                $config['reference']['db']['host'],
                $config['reference']['db']['user'],
                $config['reference']['db']['passwd'],
                $config['reference']['db']['database'],
                $config['reference']['db']['database']
            );
            $this->ssh($command, '~', $config['reference']['host'], $config['reference']['user']);

            // compress
            $command = sprintf('gzip %s-dump.sql',
                $config['reference']['db']['database']
            );
            $this->ssh($command, '~', $this->projectConfig['reference']['host'], $this->projectConfig['reference']['user']);

            // fetch dump in vm
            $command = sprintf('scp %s@%s:%s-dump.sql.gz %s',
                $config['reference']['user'],
                $config['reference']['host'],
                $config['reference']['db']['database'],
                $config['docker']['path']
            );
            $this->ssh($command);

            // uncompress in VM
            $command = sprintf('gunzip %s-dump.sql.gz', $config['reference']['db']['database']);
            $this->ssh($command, $config['docker']['path']);

            // import in appli
            $command = sprintf('mysql -h%s -u%s -p%s -e "CREATE SCHEMA IF NOT EXISTS %s;"',
                '127.0.0.1',
                $config['db']['user'],
                $config['db']['passwd'],
                $config['db']['database'],
                $config['db']['database']
            );
            $this->ssh($command, $config['docker']['path']);

            $command = sprintf('mysql -h%s -u%s -p%s %s < %s-dump.sql',
                '127.0.0.1',
                $config['db']['user'],
                $config['db']['passwd'],
                $config['db']['database'],
                $config['db']['database']
            );
            $this->ssh($command, $config['docker']['path']);

        } catch (\Throwable $e) {
            throw $e;
        } finally {
            // delete dump on reference
            $command = sprintf('rm %s-dump.sql.gz',
                $config['reference']['db']['database']
            );
            $this->ssh($command, '~', $this->projectConfig['reference']['host'],
                $this->projectConfig['reference']['user']);

            // delete dump on VM
            $command = sprintf('rm %s-dump.sql', $config['reference']['db']['database']);
            $this->ssh($command, $config['docker']['path']);
        }
    }


}
