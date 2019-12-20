<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Down extends AbstractDeckleCommand
{
    protected function configure()
    {
        $this->setName('down')
            ->setDescription('Stop running the current project')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conf = $this->config;

        Deckle::print('Stopping project <info>%s</info>', $conf['project']['name']);

        $this->runCommand('sync', ['--no-interaction' => true, 'cmd' => 'stop']);
        $this->runCommand('dc', ['--no-interaction' => true, 'args' => ['stop']]);

        Deckle::print('<info>Project stopped!</info>');
    }


}
