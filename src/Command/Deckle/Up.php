<?php

namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Up extends AbstractDeckleCommand
{
    protected function configure()
    {
        $this->setName('up')
            ->setDescription('Start running the current project')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Tells deckle to fully initialize the project again');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conf = $this->config;
        $reset = $input->getOption('reset');

        Deckle::print('Starting project <info>%s</info>', $conf['project']['name']);

        $this->runCommand('init', ['--no-interaction' => true, '--reset' => $reset]);
        $this->runCommand('push', ['--no-interaction' => true, '--reset' => $reset]);
        // add reference key to known hosts if needed
        if ($host = $this->getConfig('reference.host')) {
            $this->runCommand('vm:ssh:add-host', ['--no-interaction' => true, 'host' => $host]);
        }
        $this->runCommand('docker:compose', ['--no-interaction' => true, 'args' => ['up', '-d']]);
        $this->runCommand('mutagen:sync', ['--no-interaction' => true, 'cmd' => 'start', '--force' => true]);
        $this->runCommand('mutagen:monitor', ['--no-interaction' => true, '--until-sync' => true]);

        Deckle::success('Project started! Enjoy your deckle development environment :)');
        Deckle::note([
            'If your project needs a database, you may fetch it from a reference instance',
            'To do so, please try running: "deckle db:import"'
        ]);
    }

}
