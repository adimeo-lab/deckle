<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Drupal8Init extends AbstractDrupal8Command
{

    protected function configure()
    {
        parent::configure();

        $this->setName('drupal8:init')
            ->setDescription('Initialize development environment for Drupal 8 project')
            ->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Initializing Drupal 8 project <comment>' . $this->projectConfig['project']['name'] . '</comment>');

        // push Docker config
        $command = $this->getApplication()->find('push');
        $command->setProjectConfig($this->getProjectConfig());
        $arguments = [
            'command' => 'push'
        ];

        $input = new ArrayInput($arguments);
        $command->run($input, $output);

    }


}
