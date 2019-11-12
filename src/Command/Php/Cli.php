<?php


namespace Adimeo\Deckle\Command\Php;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Cli extends AbstractDeckleCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('php:php')
            ->setAliases(['php']);
        $this->addArgument('args', InputArgument::IS_ARRAY|InputArgument::OPTIONAL, 'PHP arguments');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $this->loadProjectConfig();

        if($output->isVerbose()) {
            $output->writeln('Executing <comment>php</comment> on remote container');

            $path = $this->projectConfig['app']['path'];

            $this->runCommandInContainer('php ', $this->input->getArgument('args'), $path);
        }
    }
}