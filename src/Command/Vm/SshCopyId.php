<?php


namespace Adimeo\Deckle\Command\Vm;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Deckle\Install;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SshCopyId extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{
    protected function configure()
    {
        $this->setName('vm:ssh:copy-id')
            ->setDescription('Copy your SSH public key to deckle vm')
            ->addOption('identity', 'i', InputOption::VALUE_OPTIONAL, 'RSA public key to copy to deckle vm', '~/.ssh/id_rsa.pub')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getProjectConfig();
        $key = $input->getOption('identity');
        $host = $config['vm']['host'] ?? null;
        $user = $config['vm']['user'] ?? null;
        if(!$host || !$user) {
            $this->error('No Deckle VM configuration found. If you\‘re outside of a Deckle project, please define vm[host] and vm[user] in your %s/deckle.local.yml configuration file.', [Install::DECKLE_HOME]);
        }
        $output->writeln('Copying <info>' .  $key . '</info> to <comment>'. $user . '@' . $host .'</comment> ...');
        passthru(sprintf('ssh-copy-id -i %s %s@%s', $key, $user, $host));
    }

}
