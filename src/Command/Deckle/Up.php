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

    const EVENT_INIT_POST = 'up.init.post';
    const EVENT_PUSH_POST = 'up.push.post';
    const EVENT_DC_UP_POST = 'up.dc-up.post';
    const EVENT_FINISHED = 'up.finished';

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

        Deckle::runCommand('init', ['--no-interaction' => true, '--reset' => $reset]);
        Deckle::events()->trigger('up.init.post');
        Deckle::runCommand('push', ['--no-interaction' => true, '--reset' => $reset]);
        Deckle::events()->trigger('up.push.post');
        Deckle::runCommand('docker:compose', ['--no-interaction' => true, 'args' => ['up', '-d']]);
        Deckle::events()->trigger('up.dc-up.post');
        Deckle::runCommand('mutagen:sync', ['--no-interaction' => true, 'cmd' => 'start', '--force' => true]);
        Deckle::runCommand('mutagen:monitor', ['--no-interaction' => true, '--until-sync' => true]);
        Deckle::events()->trigger('up.finished');

        Deckle::success('Project started! Enjoy your deckle development environment :)');

    }



}
