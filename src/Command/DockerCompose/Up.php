<?php


namespace Adimeo\Deckle\Command\DockerCompose;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Up
 * @package Adimeo\Deckle\Command\DockerCompose
 */
class Up extends AbstractDeckleCommand
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('dc:up')
            ->setDescription('Start environment')
            ->addOption('docker-compose-file', 'dcf', InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'docker-compose.yml file(s) to use (relative to ./deckle/<<dev|prod path>>)', ['docker-compose.yml'])
            ->addOption('build', null, InputOption::VALUE_NONE, 'Rebuild all containers or the specified as argument')
            ->addOption('force-recreate', null, InputOption::VALUE_NONE, 'Force container to be destroyed and created again (will change its id)')
            ->addArgument('container', InputArgument::OPTIONAL, 'Container to start (and optionally build');

            // load environment
            $this->loadEnvironment();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {


    }

}
