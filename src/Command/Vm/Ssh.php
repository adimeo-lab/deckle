<?php


namespace Adimeo\Deckle\Command\Vm;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Deckle\InstallMacOs;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Service\Shell\Script\Location\DeckleMachine;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Ssh extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{
    protected function configure()
    {
        $this->setName('vm:ssh')
            ->addOption('working-directory', 'wd', InputOption::VALUE_OPTIONAL, 'Set the working directory on Deckle Machine <comment>for executing remote command only</comment>')
            ->addArgument("cmd", InputArgument::OPTIONAL, "Command to execute over SSH instead of opening a remote session")
            ->setDescription('Open a SSH session to your Deckle Machine or run a command on it');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if($cmd = $input->getArgument('cmd')) {
            $cwd = $input->getOption('working-directory') ?? "~";
            $target = new DeckleMachine($cwd);
            $return = $this->sh()->exec($cmd, $target);
            if($output->isVerbose() || $return->isErrored()) {
                $style = $return->isErrored() ? 'error' : 'info';
                Deckle::print('<' . $style . '>SSH command output:</' . $style .'>');
                Deckle::print(implode("\n", $return->getOutput()));
            }
        } else {
            $deckle = new DeckleMachine();
            $this->sh()->completeDeckleMachineLocation($deckle);
            Deckle::print('Opening a SSH session to <info>' . $deckle->getUser() . '</info>@<info>' . $deckle->getHost() . '</info> ...');
            passthru('ssh ' . $deckle->getUser() . '@' . $deckle->getHost());
        }
    }

}
