<?php


namespace Adimeo\Deckle\Command\Docker;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Service\Shell\Script\Location\Container;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Shell extends AbstractDeckleCommand
{
    protected function configure()
    {
        $this->setName('docker:shell')
            ->setAliases(['sh'])
            ->addArgument('container', InputArgument::OPTIONAL, 'Container to log in')
            ->addOption('shell', 's', InputOption::VALUE_OPTIONAL, 'Shell to open');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $containerName = $input->getArgument('container') ?: $this->getConfig()->get('app.container');
        $shell = $input->getOption('shell') ?? $this->config['defaults']['shell'] ?? 'bash';

        $this->sh()->exec($shell, new Container($containerName, '~'), false);
    }

}
