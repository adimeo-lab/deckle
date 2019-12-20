<?php


namespace Adimeo\Deckle\Command\Php;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Service\Shell\Script\Location\AppContainer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Cli extends AbstractDeckleCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('php:exec')
            ->addArgument('args', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'PHP arguments');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Deckle::print('Executing <comment>php</comment> on remote container');
        $path = $this->config['app']['path'];
        $this->sh()->exec('php ' . implode(' ', $this->input->getArgument('args')), new AppContainer($path), false);
    }
}
