<?php


namespace Adimeo\Deckle\Command\Symfony;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SfInit extends AbstractDeckleCommand
{
    protected function configure()
    {
        $this->setName('symfony:init')
            ->setAliases(['sf4:init', 'sf5:init'])
            ->setDescription('Initialize Symfony project')
            ->setHidden(true)
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output); // TODO: Change the autogenerated stub
    }

}