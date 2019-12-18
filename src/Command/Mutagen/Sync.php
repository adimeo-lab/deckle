<?php


namespace Adimeo\Deckle\Command\Mutagen;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Sync extends AbstractMutagenCommand
{
    /**
     * @var OutputInterface
     */
    protected $actualOutput;

    protected function configure()
    {
        $this->setName('mutagen:sync')
            ->setAliases(['sync'])
            ->setDescription('Mutagen sessions controller')
            ->addOption('force', 'f', InputOption::VALUE_NONE,'Force action (make "start" restart if already running)')
            ->addArgument('cmd', InputArgument::REQUIRED, 'mutagen operation to execute (start, stop, restart');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->actualOutput = $output;

        if (!is_file('deckle/mutagen.yml')) {
            $this->error('No deckle/mutagen.yml file was found. Cannot control mutagen for this project.');
        }


        $running = $this->isMutagenUp();
        $cmd = $input->getArgument('cmd');


        if($cmd == 'start' && $input->getOption('force')) {
            if ($running) {
                $cmd = 'restart';
            } else {
                $cmd = 'start';
            }
        }

        switch ($cmd) {

            case 'restart':
                if ($running) {
                    $this->output->writeln('Restarting <info>mutagen</info> project...');
                    system('mutagen project terminate deckle/mutagen.yml');
                    system('mutagen project start deckle/mutagen.yml');
                    break;
                } else {
                    $this->output->writeln('<info>mutagen</info> session(s) not running. Starting sessions...');
                    $cmd = 'start';
                }


            case 'start':
                if ($running) {
                    $this->output->writeln('<info>mutagen</info> session(s) already up');
                    return 0;
                }
                system('mutagen project start deckle/mutagen.yml');
                break;

            case 'stop':
            case 'terminate':
                if (!$running) {
                    $this->output->writeln('<info>mutagen</info> session(s) not running');
                    return 0;
                }
                system('mutagen project terminate deckle/mutagen.yml');
                $this->output->writeln('<info>mutagen</info> session(s) terminated');
                break;

            case 'status':
                $this->displayStatus($output->isVerbose());
                break;

            default:
                $this->error('Unknown mutagen operation "%s"', [$cmd]);
                break;
        }
    }


    protected function displayStatus($extended = false)
    {


        if (!$this->isMutagenUp()) {
            $this->output->writeln("<info>mutagen</info> seems not to be running. Run <comment>deckle sync start</comment> to start your synchronisation sessions.");
        }

        $sessions = $this->fetchSessionsStatus();

        foreach ($sessions as $session => $info) {
            $this->output->writeln(sprintf('Session <info>%s</info>: ' . "     \t" . '<comment>%s</comment>', $session,
                $info['Status']));
            if ($extended) {
                $this->output->writeln(str_repeat(" ", 2) . "<info>Alpha</info>:");
                foreach ($info['alpha'] as $item => $value) {
                    $this->output->writeln(sprintf(str_repeat(" ", 4) . "%s: <comment>%s</comment>", $item, $value));
                }

                $this->output->writeln(str_repeat(" ", 2) . "<info>Beta</info>:");
                foreach ($info['beta'] as $item => $value) {
                    $this->output->writeln(sprintf(str_repeat(" ", 4) . "%s: <comment>%s</comment>", $item, $value));
                }
                $this->output->writeln("----------------------------------------");
            }

        }
    }

}
