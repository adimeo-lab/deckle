<?php


namespace Adimeo\Deckle\Command\Vm;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Deckle\InstallMacOs;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddKnownHost extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{
    protected function configure()
    {
        $this->setName('vm:ssh:add-host')
            ->setDescription('Add given host to known hosts')
            ->setHidden(true)
        ->addArgument('host', InputArgument::REQUIRED, 'Host to add to ~/.ssh/known_hosts');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getProjectConfig();

        $host = $config['vm']['host'] ?? null;
        $user = $config['vm']['user'] ?? null;

        if(!$host || !$user) {
            $this->error('No Deckle VM configuration found. If you\‘re outside of a Deckle project, please define vm[host] and vm[user] in your %s/deckle.local.yml configuration file.', [InstallMacOs::DECKLE_HOME]);
        }

        $newHost = $input->getArgument('host');

        $output->writeln('Adding a SSH host to known hosts for <comment>'. $user . '@' . $host .'</comment> ...');
        $this->ssh(sprintf('ssh-keyscan -H %s >> ~/.ssh/known_hosts', $newHost));
    }

}
