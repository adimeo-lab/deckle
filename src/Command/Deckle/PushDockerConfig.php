<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Exception\DeckleException;
use Adimeo\Deckle\Service\Shell\Script\Location\DeckleMachine;
use Adimeo\Deckle\Service\Shell\Script\Location\LocalPath;
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
            ->setDescription('Push deckle/docker configuration files to Deckle Machine')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // check if project already exists
        // TODO find another way to test directory existence silently!

        $return = $this->sh()->exec('ls ' . $this->config['docker']['path'], new DeckleMachine());

        if(!$return->isErrored()) {
            if(!$input->getOption('reset')) {
                if($input->isInteractive()) {
                    $this->halt('Project already exists on VM. Please use the "--reset" switch to clear previous remote environment.');
                } else {
                    return -1;
                }
            }
             else {
                 // delete remote environment
                 $return = $this->sh()->exec('rm -rf ' . $this->config['docker']['path'], new DeckleMachine());
                 if($return->isErrored()) {
                     $this->error('An error occurred while deleting remote directory "%s"', [$this->config['docker']['path']]);
                 }
             }
        }

        // copy Docker configuration to remote environment
        $output->writeln(sprintf('Pushing <comment>deckle/docker</comment> to <comment>%s</comment> in Deckle Machine', $this->config['docker']['path']));
        $this->sh()->scp(new LocalPath('deckle/docker'), new DeckleMachine($this->getConfig('docker.path')));

    }

}
