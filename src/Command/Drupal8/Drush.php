<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Drush extends AbstractDrupal8Command
{
    protected function configure()
    {
        parent::configure();
        $this->setName('drupal8:drush')
            ->setAliases(['drush']);
        $this->addArgument('args', InputArgument::IS_ARRAY|InputArgument::OPTIONAL, 'Drush arguments');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if($output->isVerbose()) {
            $output->writeln('Executing <comment>drush</comment> on remote container');

            $path = $this->projectConfig['app']['path'];

            $this->runCommandInContainer('vendor/bin/drush', $this->input->getArgument('args'), $path);
        }
    }


}
