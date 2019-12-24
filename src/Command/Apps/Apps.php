<?php


namespace Adimeo\Deckle\Command\Apps;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Deckle\Install;
use Adimeo\Deckle\Command\Deckle\Installer\MacOsInstaller;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Service\Shell\Script\Location\DeckleMachine;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Adimeo\Deckle\Deckle;

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
            Deckle::error(['No Deckle Machine configuration found.',  'If you are outside of a Deckle project, please define', 'vm[host] and vm[user] in your %s/deckle.local.yml configuration file.'], [Install::DECKLE_HOME]);
        }

        $apps = $input->getArgument('app');
        $action = $input->getArgument('action');

        $availableApps = $this->listAvailableApps();

        $error = false;
        foreach ($apps as $app) {
            if (!in_array($app, $availableApps)) {
                Deckle::print('<error>Unknown specified app: <info>' . $app . '</info></error>');
                $action = 'list';
                $error = true;
            }
        }

        if(!$apps) $apps = $availableApps;

        if($error) Deckle::print('');

        switch($action) {
            case 'list':
                Deckle::print('Available apps on Deckle Machine:');
                Deckle::print('');
                foreach ($availableApps as $app) Deckle::print("\t - <info>" . $app . "</info>");
                Deckle::print('');
                break;

            case 'start':
                foreach($apps as $app) {
                    Deckle::print('Starting app <info>' . $app . '</info>');
                    $this->sh()->exec('docker-compose up -d', new DeckleMachine('~/apps/' . $app));
                }
                break;

            case 'rebuild':
                $appsToRebuild = $apps;
                foreach($appsToRebuild as &$str) $str = '<info>' . $str . '</info>';
                Deckle::print('About to rebuild ' . implode(', ', $appsToRebuild));
                if(Deckle::confirm('Rebuilding an app can lead to data loss in your container. Are you sure you want to do this?', false)) {
                    foreach ($apps as $app) {
                        Deckle::print('Rebuilding app <info>' . $app . '</info>');
                        $this->sh()->exec('docker-compose up --build --force-recreate -d --remove-orphans', new DeckleMachine('~/apps/' . $app));
                    }
                }
                break;

            case 'stop':
                foreach($apps as $app) {
                    Deckle::print('Stopping app <info>' . $app . '</info>');
                    $this->sh()->exec('docker-compose stop', new DeckleMachine('~/apps/' . $app));
                }
                break;

            case 'restart':
                foreach($apps as $app) {
                    Deckle::print('Restarting app <info>' . $app . '</info>');
                    $this->sh()->exec('docker-compose restart', new DeckleMachine('~/apps/' . $app));
                }
                break;

            case 'status':
                foreach($apps as $app) {
                    Deckle::print('Reporting status of app <info>' . $app . '</info>');
                    $return = $this->sh()->exec('docker-compose ps', new  DeckleMachine('~/apps/' . $app));
                    Deckle::print($return->getOutput());
                    Deckle::br();

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
