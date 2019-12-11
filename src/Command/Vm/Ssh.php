<?php


namespace Adimeo\Deckle\Command\Vm;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Deckle\InstallMacOs;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Ssh extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{
    protected function configure()
    {
        $this->setName('vm:ssh')
            ->setDescription('Open a SSH session to your dev VM');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getProjectConfig();

        $host = $config['vm']['host'] ?? null;
        $user = $config['vm']['user'] ?? null;
        if(!$host || !$user) {
            $this->error('No Deckle VM configuration found. If you\‘re outside of a Deckle project, please define vm[host] and vm[user] in your %s/deckle.local.yml configuration file.', [InstallMacOs::DECKLE_HOME]);
        }
        $output->writeln('Opening a SSH session to <comment>'. $user . '@' . $host .'</comment> ...');
        passthru('ssh ' . $user . '@' . $host);
    }

}
