<?php


namespace Adimeo\Deckle\Command\Symfony;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Deckle\Up;
use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SfInit extends AbstractDeckleCommand
{
    protected function configure()
    {
        $this->setName('symfony:init')
            ->setAliases(['sf4:init', 'sf5:init'])
            ->setDescription('Initialize Symfony project')
            ->addOption('reset', 'r', InputOption::VALUE_NONE)
            ->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Deckle::events()->bind(Up::EVENT_DC_UP_POST, function () {
            Deckle::runCommand('composer', ['args' => ['install']]);
        });

        Deckle::events()->bind(Up::EVENT_FINISHED, function () {
            Deckle::note([
                'If your project needs a database, you may fetch it from a reference instance',
                'To do so, please try running: "deckle db:import"'
            ]);
        });
    }

}
