<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Service\Shell\Script\Location\AppContainer;
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

        Deckle::print('Executing <comment>drush</comment> on remote container');

        $path = $this->config['app']['path'];

        $this->sh()->exec('vendor/bin/drush' .  implode(' ', $this->input->getArgument('args')), new AppContainer($path), false);
    }


}
