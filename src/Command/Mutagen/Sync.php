<?php


namespace Adimeo\Deckle\Command\Mutagen;


use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Service\Shell\Script\Location\LocalPath;
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
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force action (make "start" restart if already running)')
            ->addArgument('cmd', InputArgument::REQUIRED, 'mutagen operation to execute (start, stop, restart');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->actualOutput = $output;

        if (!is_file('deckle/mutagen.yml')) {
            Deckle::error('No deckle/mutagen.yml file was found. Cannot control mutagen for this project.');
        }


        $running = $this->isMutagenUp();
        $cmd = $input->getArgument('cmd');


        if ($cmd == 'start' && $input->getOption('force')) {
            if ($running) {
                $cmd = 'restart';
            } else {
                $cmd = 'start';
            }
        }

        switch ($cmd) {

            case 'restart':
                if ($running) {
                    Deckle::print('Restarting <info>mutagen</info> project...');
                    system('mutagen project terminate deckle/mutagen.yml');
                    system('mutagen project start deckle/mutagen.yml');
                    Deckle::runCommand('mutagen:monitor', ['--until-sync' => true]);
                    break;
                } else {
                    Deckle::print('<info>mutagen</info> session(s) not running. Starting sessions...');
                    $this->sh()->exec('mutagen project start', new LocalPath('./deckle'), false);
                    Deckle::runCommand('mutagen:monitor', ['--until-sync' => true]);
                }


            case 'start':
                if ($running) {
                    Deckle::print('<info>mutagen</info> session(s) already up');
                    return 0;
                }
                Deckle::print('Starting <info>mutagen</info> session(s)...');
                $this->sh()->exec('mutagen project start', new LocalPath('./deckle'), false);
                Deckle::runCommand('mutagen:monitor', ['--until-sync' => true]);
                break;

            case 'stop':
            case 'terminate':
                if (!$running) {
                    Deckle::print('<info>mutagen</info> session(s) not running');
                    return 0;
                }
                Deckle::print('Terminating <info>mutagen</info> session(s)');
                $this->sh()->exec('mutagen project terminate', new LocalPath('./deckle'), false);
                break;

            case 'status':
                $this->displayStatus($output->isVerbose());
                break;

            default:
                Deckle::error('Unknown mutagen operation "%s"', [$cmd]);
                break;
        }
    }


    protected function displayStatus($extended = false)
    {
        if (!$this->isMutagenUp()) {
            Deckle::halt('Mutagen seems not to be running. You may should run "deckle sync start"');
        }

        $sessions = $this->fetchSessionsStatus();

        if (!$sessions) {
            Deckle::halt("There is no active Mutagen sessions. You are probably not in a Deckle project.");
        }

        foreach ($sessions as $session => $info) {
            $style = isset($info['conflicted']) ? 'error' : 'info';
            Deckle::print('Session <%s>%s</%1$s>' . " => " . '<comment>%s</comment>', [$style, $session,
                $info['Status']]);
            if ($extended) {
                Deckle::print(str_repeat(" ", 2) . "<info>Alpha</info>:");
                foreach ($info['alpha'] as $item => $value) {
                    Deckle::print(sprintf(str_repeat(" ", 4) . "%s: <comment>%s</comment>", $item, $value));
                }

                Deckle::print(str_repeat(" ", 2) . "<info>Beta</info>:");
                foreach ($info['beta'] as $item => $value) {
                    Deckle::print(sprintf(str_repeat(" ", 4) . "%s: <comment>%s</comment>", $item, $value));
                }
                Deckle::print("----------------------------------------");
            }

        }
    }

}
