<?php


namespace Adimeo\Deckle\Command\Docker;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Exception\DeckleException;
use Adimeo\Deckle\Service\Shell\Script\Location\DeckleMachine;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Up
 * @package Adimeo\Deckle\Command\DockerCompose
 */
class Compose extends AbstractDeckleCommand
{
    /**
     *
     */
    protected function configure()
    {

        $this->setName('docker:compose')
            ->setDescription('Run docker-compose in VM')
            ->setAliases(['dc'])
            ->addArgument('args', InputArgument::IS_ARRAY|InputArgument::OPTIONAL, 'docker-compose arguments')
           ;

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws DeckleException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $silent = false;
        $args = implode(' ', $input->getArgument('args'));

        $this->sh()->exec('docker-compose ' . $args, new DeckleMachine($this->getConfig('docker.path')), $silent);
    }


}
