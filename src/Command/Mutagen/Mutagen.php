<?php


namespace Adimeo\Deckle\Command\Mutagen;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Mutagen extends AbstractDeckleCommand
{
    protected function configure()
    {
        $this->setName('mutagen:sync')
            ->setAliases(['sync'])
        ->setDescription('Wrapper for mutagen')
        ->addArgument('cmd', InputArgument::REQUIRED, 'mutagen operation to execute');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!is_file('deckle/mutagen.yml')) {
            $this->error('No deckle/mutagen.yml file was found. Cannot control mutagen for this project.');
        }


        $running = $this->isRunning();

        switch($cmd = $input->getArgument('cmd')) {

            case 'restart':
                if($running) {
                    system('mutagen project terminate deckle/mutagen.yml');
                    system('mutagen project start deckle/mutagen.yml');
                    break;
                } else {
                    $this->output->writeln('<info>mutagen</info> session(s) not running. Starting sessions...');
                    $cmd = 'start';
                }

            case 'start':
                if($running) {
                    $this->output->writeln('<info>mutagen</info> session(s) already up');
                    return 0;
                }
                system('mutagen project start deckle/mutagen.yml');
                break;

            case 'stop':
            case 'terminate':
                if(!$running) {
                    $this->output->writeln('<info>mutagen</info> session(s) not running');
                    return 0;
                }
                system('mutagen project terminate deckle/mutagen.yml');
                $this->output->writeln('<info>mutagen</info> session(s) terminated');
                break;



            case 'monitor':
            case 'status':
                $this->displayStatus($output->isVerbose());
                break;

            default:
                $this->error('Unknown mutagen operation "%s"', [$cmd]);
                break;
        }
    }

    protected function displayStatus($extended = false) {


        if(!$this->isRunning()) {
            $this->output->writeln("<info>mutagen</info> seems not to be running. Run <comment>deckle sync start</comment> to start your synchronisation sessions.");
        }

        $sessions = $this->fetchSessionsStatus();

        foreach ($sessions as $session => $info) {
            $this->output->writeln(sprintf('Session <info>%s</info>: ' . "     \t" . '<comment>%s</comment>', $session, $info['Status']));
            if($extended) {
                $this->output->writeln(str_repeat(" ", 2) . "<info>Alpha</info>:");
                foreach($info['alpha'] as $item => $value) {
                    $this->output->writeln(sprintf(str_repeat(" ", 4) . "%s: <comment>%s</comment>", $item, $value));
                }

                $this->output->writeln(str_repeat(" ", 2) . "<info>Beta</info>:");
                foreach($info['beta'] as $item => $value) {
                    $this->output->writeln(sprintf(str_repeat(" ", 4) . "%s: <comment>%s</comment>", $item, $value));
                }
                $this->output->writeln("----------------------------------------");
            }

        }
    }

    protected function isRunning()
    {
         exec('mutagen project list deckle/mutagen.yml 2>&1', $output, $return);

         return !$return;
    }

    protected function fetchSessionsStatus()
    {
        exec('mutagen project list deckle/mutagen.yml 2>&1', $output, $return);

        $forwardingSessions = [];
        $syncSessions = [];

        $currentSection = null;
        $session = null;
        $parsingEndpoint = false;

        foreach($output as $line) {

            // skipped deepest info
            if(strpos($line, "\t\t") === 0) continue;
            if($line == "No sessions found") continue;
            if(!$line) continue;


            if($line == 'Forwarding sessions:')
            {
                $currentSection = &$forwardingSessions;
                continue;
            }

            if($line == 'Synchronization sessions:')
            {
                $currentSection = &$syncSessions;
                continue;
            }

            if(preg_match('/Name: (.*)/', $line, $matches))
            {
                $currentSection[$matches[1]] = [];
                $session = &$currentSection[$matches[1]];
                continue;
            }

            if(preg_match('/^(\w*):$/', $line, $matches))
            {
                $section = strtolower($matches[1]);
                if(!in_array($section, ['alpha', 'beta'])) continue;
                $session[$section] = [];
                $parsingEndpoint = $section;
                continue;
            }

            if($line =='Beta:')
            {
                $session['beta'] = [];
                $parsingEndpoint = 'beta';
                continue;
            }

            if(preg_match('/^\-+$/', $line)) {
                $parsingEndpoint = false;
                continue;
            }

            if($parsingEndpoint && !(strpos($line, "\t") === 0)) {
                $parsingEndpoint = false;
            }

            if($parsingEndpoint) {
                [$info, $value] = explode(':', $line, 2);
                $session[$parsingEndpoint][trim($info)] = trim($value);
                continue;
            } else {
                if(strpos($line, "\t") === 0) continue;
                [$info, $value] = explode(':', $line, 2);
                $session[trim($info)] = trim($value);
                continue;
            }



        }

        return $syncSessions;
    }
}
