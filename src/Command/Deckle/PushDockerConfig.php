<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PushDockerConfig extends AbstractDeckleCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('deckle:push')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Delete existing remote files if exist')
        ->setAliases(['push']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // check if project already exists
        $return = $this->ssh('ls ' . $this->projectConfig['docker']['path'] . ' > /dev/null ');

        if(!$return) {
            if(!$input->getOption('reset')) {
                throw new DeckleException('Project already exists on VM. Please use the "--reset" switch to clear previous remote environment.');
            }
             else {
                 // delete remote environment
                 $return = $this->ssh('rm -rf ' . $this->projectConfig['docker']['path']);
                 if($return) {
                     throw new DeckleException('An error occurred while deleting remote directory ' . $this->projectConfig['docker']['path']);
                 }
             }
        }

        // copy Docker configuration to remote environment
        $this->scp('deckle/docker', $this->projectConfig['docker']['path']);

        // generate .env for docker-compose
        $envVars = [];

        $envVars['COMPOSE_PROJECT_NAME'] = $this->projectConfig['project']['name'];
        $envVars['APACHE_PORT'] = $this->projectConfig['app']['port'];

        $env = '';
        foreach ($envVars as $var => $value) {
            $env .= $var . '=' . $value . PHP_EOL;
        }

        $this->ssh('touch ' . $this->projectConfig['docker']['path'] . '/.env');
        $this->ssh('echo "' . $env . '" > ' . $this->projectConfig['docker']['path'] . '/.env');
    }

}
