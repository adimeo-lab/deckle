<?php


namespace Adimeo\Deckle\Command\Symfony;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Service\Shell\Script\Location\AppContainer;
use Adimeo\Deckle\Service\Shell\Script\Location\LocalPath;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Console extends AbstractDeckleCommand
{
    protected function configure()
    {
        $this->setName('symfony:console')
            ->setAliases(['console'])
            ->setDescription('Execute Symfony console in app container')
            ->addOption('dump-sql', 'd', InputOption::VALUE_NONE)
            ->addArgument('args', InputArgument::IS_ARRAY, 'Symfony console arguments', []);
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $args = implode(' ', $input->getArgument('args'));
        $path = $this->getConfig('app.path');
        $this->sh()->exec('vendor/bin/console ' . $args, new AppContainer($path));
    }

}