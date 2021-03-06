<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Exception\DeckleException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Init extends AbstractDeckleCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('init')
            ->setDescription('Set up your local Deckle project')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Overwrite previously processed files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if (!is_dir('./deckle')) {
            Deckle::error('No "./deckle" folder found. You may need to bootstrap your project.');
        }

        $type = $this->getConfig('project.type');

        $initCommand = $type . ':init';

        $command = $this->getApplication()->find($initCommand);

        if(!$command) {
            Deckle::halt('Unable to init "%s" projects.', [$type]);
        }

        Deckle::runCommand($command->getName(), ['--reset' => $input->getOption('reset')]);
    }

}
