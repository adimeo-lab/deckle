<?php


namespace Adimeo\Deckle\Command\Apps;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Deckle\InstallMacOs;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Service\Shell\Script\Location\DeckleMachine;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Apps extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{


    protected function configure()
    {
        $this->setName('apps')
            ->addArgument('action', InputArgument::OPTIONAL, 'Action to perform on apps (start, stop, restart, rebuild', 'list')
            ->addArgument('app', InputArgument::IS_ARRAY, 'App(s) to perform action against. Default: all available apps')
            ->setDescription('Manage apps installed in Deckle Machine');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getConfig();

        $host = $config['vm']['host'] ?? null;
        $user = $config['vm']['user'] ?? null;
        if(!$host || !$user) {
            $this->error('No Deckle Machine configuration found. If you\‘re outside of a Deckle project, please define vm[host] and vm[user] in your %s/deckle.local.yml configuration file.', [InstallMacOs::DECKLE_HOME]);
        }

        $apps = $input->getArgument('app');
        $action = $input->getArgument('action');

        $availableApps = $this->listAvailableApps();

        $error = false;
        foreach ($apps as $app) {
            if (!in_array($app, $availableApps)) {
                $output->writeln('<error>Unknown specified app: <info>' . $app . '</info></error>');
                $action = 'list';
                $error = true;
            }
        }

        if(!$apps) $apps = $availableApps;

        if($error) $output->writeln('');

        switch($action) {
            case 'list':
                $output->writeln('Available apps on Deckle Machine:');
                $output->writeln('');
                foreach ($availableApps as $app) $output->writeln("\t - <info>" . $app . "</info>");
                $output->writeln('');
                break;

            case 'start':
                foreach($apps as $app) {
                    $output->writeln('Starting app <info>' . $app . '</info>');
                    $this->sh()->exec('docker-compose up -d', new DeckleMachine('~/apps/' . $app));
                }
                break;

            case 'rebuild':
                $appsToRebuild = $apps;
                foreach($appsToRebuild as &$str) $str = '<info>' . $str . '</info>';
                $output->writeln('About to rebuild ' . implode(', ', $appsToRebuild));
                if($this->confirm('Rebuilding an app can lead to data loss in your container. Are you sure you want to do this?')) {
                    foreach ($apps as $app) {
                        $output->writeln('Rebuilding app <info>' . $app . '</info>');
                        $this->sh()->exec('docker-compose up --build --force-recreate -d --remove-orphans', new DeckleMachine('~/apps/' . $app));
                    }
                }
                break;

            case 'stop':
                foreach($apps as $app) {
                    $output->writeln('Stopping app <info>' . $app . '</info>');
                    $this->sh()->exec('docker-compose stop', new DeckleMachine('~/apps/' . $app));
                }
                break;

            case 'restart':
                foreach($apps as $app) {
                    $output->writeln('Restarting app <info>' . $app . '</info>');
                    $this->sh()->exec('docker-compose restart', new DeckleMachine('~/apps/' . $app));
                }
                break;

            case 'status':
                foreach($apps as $app) {
                    $output->writeln('Reporting status of app <info>' . $app . '</info>');
                    $return = $this->sh()->exec('docker-compose ps', new  DeckleMachine('~/apps/' . $app));
                    $output->writeln(implode("\n", $return->getOutput()));
                    $output->writeln('');

                }
                break;
        }

    }

    protected function listAvailableApps()
    {
        $return = $this->sh()->exec('for i in $(find ./ -mindepth 1 -maxdepth 1 -type d);do if [ -f $i/docker-compose.yml ]; then echo $i; fi; done;', new DeckleMachine('~/apps'));
        $apps = $return->getOutput();
        foreach($apps as &$app) {
            $app = str_replace('./','', $app);
        }
        return $apps;

    }
}
