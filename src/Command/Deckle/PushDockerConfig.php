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

        $this->setName('push')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Delete existing remote files if exist')
            ->setDescription('Push deckle/docker configuration files to Deckle VM')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // check if project already exists
        // TODO find another way to test directory existence silently!

        $return = $this->ssh('ls ' . $this->projectConfig['docker']['path'] . ' 2>&1');

        if(!$return) {
            if(!$input->getOption('reset')) {
                if($input->isInteractive()) {
                    $this->halt('Project already exists on VM. Please use the "--reset" switch to clear previous remote environment.');
                } else {
                    return 0;
                }
            }
             else {
                 // delete remote environment
                 $return = $this->ssh('rm -rf ' . $this->projectConfig['docker']['path']);
                 if($return) {
                     $this->error('An error occurred while deleting remote directory "%s"', [$this->projectConfig['docker']['path']]);
                 }
             }
        }

        // copy Docker configuration to remote environment
        $output->writeln(sprintf('Pushing <comment>deckle/docker</comment> to <comment>%s</comment> on development VM', $this->projectConfig['docker']['path']));
        $this->scp('deckle/docker', $this->projectConfig['docker']['path']);


    }

}
