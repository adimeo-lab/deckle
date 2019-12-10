<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Service\Misc\ArrayTool;
use mysql_xdevapi\Exception;
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

            // fetch config
            $config = $this->projectConfig;

            // complete with settings.local.php from reference if needed
            $this->fillConfigFromReferenceSettings($config);

            if(empty(    $config['reference']['db']['database'])) {
                $this->error('Looks like Deckle failed retrieving complete reference DB configuration (missing at least "database" name!)');
            }

            $command = sprintf('mysqldump -h%s -u%s -p%s %s > %s-dump.sql',
                $config['reference']['db']['host'],
                $config['reference']['db']['username'],
                $config['reference']['db']['password'],
                $config['reference']['db']['database'],
                $config['reference']['db']['database']
            );
            $this->ssh($command, '~', $config['reference']['host'], $config['reference']['user']);

            // compress
            $command = sprintf('gzip %s-dump.sql',
                $config['reference']['db']['database']
            );
            $this->ssh($command, '~', $this->projectConfig['reference']['host'],
                $this->projectConfig['reference']['user']);

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
                $config['db']['username'],
                $config['db']['password'],
                $config['db']['database'],
                $config['db']['database']
            );
            $this->ssh($command, $config['docker']['path']);

            $command = sprintf('mysql -h%s -u%s -p%s %s < %s-dump.sql',
                '127.0.0.1',
                $config['db']['username'],
                $config['db']['password'],
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

    protected function fillConfigFromReferenceSettings(array &$config)
    {

        $host = $config['reference']['host'];
        $user = $config['reference']['user'];
        $source = $config['reference']['path'] . '/web/sites/default/settings.local.php';
        $target = tempnam(sys_get_temp_dir(),'reference_');

        $scpCommand = 'scp ' . $user . '@' . $host . ':"' . $source . '" "' . $target . '"';

        system($scpCommand);

        try {
            if(!file_get_contents($target)) {
                throw new \Exception('Unable to fetch remote settings.local.php content');
            }
            require $target;
            unlink($target);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            if (file_exists($target)) {
                unlink($target);
            }
        }

        if(!isset($databases)) {
            $this->output->writeln('<danger>No database configuration found in reference configuration</danger>');
            return $config;
        } else {
            $config['reference']['db'] = array_merge($databases['default']['default'], ArrayTool::filterRecursive($config['reference']['db']) ?? []);
        }
    }
}
