<?php


namespace Adimeo\Deckle\Command\Vm;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Deckle\InstallMacOs;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Ssh extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{
    protected function configure()
    {
        $this->setName('vm:ssh')
            ->addOption('working-directory', 'cwd', InputOption::VALUE_OPTIONAL, 'Set the working directory on deckle VM <comment>for executing remote command only</comment>')
            ->addArgument("cmd", InputArgument::OPTIONAL, "Command to execute over SSH instead of opening a remote session")
            ->setDescription('Open a SSH session to your deckle VM or run a command on it');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getProjectConfig();

        $host = $config['vm']['host'] ?? null;
        $user = $config['vm']['user'] ?? null;
        if(!$host || !$user) {
            $this->error('No Deckle VM configuration found. If you are outside of a Deckle project, please define vm[host] and vm[user] in your %s/deckle.local.yml configuration file.', [InstallMacOs::DECKLE_HOME]);
        }
        if($cmd = $input->getArgument('cmd')) {
            $cwd = $input->getOption('working-directory') ?? "~";
            $this->ssh($cmd, $cwd, $host, $user);
            if($output->isVerbose()) {
                $output->writeln('<info>SSH command output:</info>');
                $output->writeln(implode("\n", $this->getLastSshCommandOutput()));
            }
        } else {
            $output->writeln('Opening a SSH session to <info>' . $user . '</info>@<info>' . $host . '</info> ...');
            passthru('ssh ' . $user . '@' . $host);
        }
    }

}
