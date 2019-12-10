<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Drupal extends AbstractDrupal8Command
{
    protected function configure()
    {
        parent::configure();
        $this->setName('drupal8:console')
            ->setAliases(['d8'])
        ->setDescription('Run Drupal 8 console');
        $this->addArgument('args', InputArgument::IS_ARRAY|InputArgument::OPTIONAL, 'Drupal console arguments');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
            $output->writeln('Executing <comment>drupal</comment> on remote container');
            $path = $this->projectConfig['app']['path'];
            $this->dockerExec('vendor/bin/drupal', $this->input->getArgument('args'), $path);
    }


}
