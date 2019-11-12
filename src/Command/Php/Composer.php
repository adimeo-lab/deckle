<?php


namespace Adimeo\Deckle\Command\Php;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Composer extends AbstractDeckleCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('php:composer')
            ->setAliases(['composer']);
        $this->addArgument('args', InputArgument::IS_ARRAY|InputArgument::OPTIONAL, 'Composer arguments');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->loadProjectConfig();

        if($output->isVerbose()) {
            $output->writeln('Executing <comment>composer</comment> on remote container');

            $path = $this->projectConfig['app']['path'];

            $this->runCommandInContainer('composer ', $this->input->getArgument('args'), $path);
        }
    }
}
