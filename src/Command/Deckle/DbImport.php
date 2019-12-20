<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Exception\DeckleException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DbImport extends AbstractDeckleCommand
{

    protected function configure()
    {
        $this->setName('db:import')
            ->setDescription('Import database from reference instance (project type dependant command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $type = $this->config['project']['type'];

        $initCommand = $type . ':db:import';
        $command = $this->getApplication()->find($initCommand);

        if(!$command) {
            Deckle::error('Unable to init "%s" projects.', [$type]);
        }


        $command->setConfig($this->getConfig());
        $arguments = [
            'command' => $initCommand
        ];

        $input = new ArrayInput($arguments);
        $command->run($input, $output);
    }

}
