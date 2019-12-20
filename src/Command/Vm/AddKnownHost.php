<?php


namespace Adimeo\Deckle\Command\Vm;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Deckle\InstallMacOs;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Service\Shell\Script\Location\DeckleMachine;
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
        $config = $this->getConfig();

        $host = $config['vm']['host'] ?? null;
        $user = $config['vm']['user'] ?? null;

        if(!$host || !$user) {
            Deckle::error('No Deckle Machine configuration found. If you\‘re outside of a Deckle project, please define vm[host] and vm[user] in your %s/deckle.local.yml configuration file.', [InstallMacOs::DECKLE_HOME]);
        }

        $newHost = $input->getArgument('host');

        Deckle::print('Adding a <info>' . $newHost . '</info> to Deckle Machine known SSH hosts...');
        $this->sh()->exec(sprintf('ssh-keyscan -H %s >> ~/.ssh/known_hosts', $newHost), new DeckleMachine());
    }

}
