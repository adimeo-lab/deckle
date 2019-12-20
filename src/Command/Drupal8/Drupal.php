<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Service\Shell\Script\Location\AppContainer;
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
        $this->addArgument('args', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Drupal console arguments');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Deckle::print('Executing <comment>drupal</comment> on remote container');
        $path = $this->config['app']['path'];
        $this->sh()->exec('vendor/bin/drupal ' . implode(' ', $this->input->getArgument('args')),
            new AppContainer($path), false);
    }


}
