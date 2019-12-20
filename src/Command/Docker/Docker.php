<?php


namespace Adimeo\Deckle\Command\Docker;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Docker extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{
    protected function configure()
    {
        $this->setName('docker')
            ->addArgument('args',InputArgument::IS_ARRAY, 'Docker command and arguments')
            ->setDescription('Wrapper for docker client to control Deckle Machine Docker server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $args = $input->getArgument('args');

        $dockerHost = $this->getConfig('docker.host');
        if(!$dockerHost) {
            Deckle::error('Docker Host is not set in Deckle configuration');
        }
        putenv("DOCKER_HOST=$dockerHost");

        $this->sh()->exec('docker ' . implode(" ", $args), null, false);

    }

}
