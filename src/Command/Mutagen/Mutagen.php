<?php


namespace Adimeo\Deckle\Command\Mutagen;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Mutagen extends AbstractDeckleCommand
{
    /**
     * @var OutputInterface
     */
    protected $actualOutput;

    protected function configure()
    {
        $this->setName('mutagen:sync')
            ->setAliases(['sync'])
        ->setDescription('Wrapper for mutagen')
        ->addArgument('cmd', InputArgument::REQUIRED, 'mutagen operation to execute')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->actualOutput = $output;

        if(!is_file('deckle/mutagen.yml')) {
            $this->error('No deckle/mutagen.yml file was found. Cannot control mutagen for this project.');
        }


        $running = $this->isRunning();


        switch($cmd = $input->getArgument('cmd')) {

            case 'start-or-restart':
                dump('start-or-restart');
                if($running) {
                    $cmd = 'restart';
                } else {
                    $cmd = 'start';
                }

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
                $this->monitorSessions();
                break;

            case 'monitor-until-synced':
                $this->monitorSessions(true);
                break;

            case 'status':
                $this->displayStatus($output->isVerbose());
                break;

            default:
                $this->error('Unknown mutagen operation "%s"', [$cmd]);
                break;
        }
    }

    protected function monitorSessions($untilSynced = false) {


        if(!$this->isRunning()) {
            $this->output->writeln("<info>mutagen</info> seems not to be running. Run <comment>deckle sync start</comment> to start your synchronisation sessions.");
        }

        $sections = [];

        $firstLoop = true;
        $frequency = 1;

        $consoleOutput = new ConsoleOutput();

        while($this->isRunning()) {
            $synced = [];
            $sessions = $this->fetchSessionsStatus();
            foreach ($sessions as $session => $info) {
                $synced[$session] = $info['Status'] == 'Watching for changes';
                if(!isset($sections[$session])) {
                    $sections[$session] = $consoleOutput->section();
                    $sections[$session]->writeLn('Fetching session <info>' . $session . '</info> status...');
                }
                $section = $sections[$session];
                $section->overwrite(sprintf('Session <info>%s</info>: ' . "     \t" . '<comment>%s</comment>',
                    $session, $info['Status']));
            }

            if($untilSynced && count(array_filter($synced)) == count($synced)) {
                $message = PHP_EOL . '<info>All sessions are now synced. Exiting monitor...</info>';
                if(isset($infoSection)) $infoSection->overwrite($message);
                else $this->output->writeln($message);
                return;
            };

            if($firstLoop) {
                $infoSection = $consoleOutput->section();
                if($untilSynced) {
                    $message = 'Monitoring Mutagen sessions until they are all synced. Please wait...';
                } else {
                    $message = 'Monitoring Mutagen sessions every <info>' . $frequency . '</info> second. Hit Ctrl+C to stop monitoring Mutagen.';
                }
                $infoSection->overwrite(PHP_EOL . $message);

                $firstLoop = false;
            }



            sleep($frequency);
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

    protected function monitorSession(string $sessionName)
    {
        if (!$this->isRunning()) {
            $this->halt("<info>mutagen</info> seems not to be running. Run <comment>deckle sync start</comment> to start your synchronisation sessions.");
        }


        /** @var ConsoleSectionOutput $section */
        $section = $this->actualOutput->section();
        $section->writeln('fetching mutagen status...');

        while($session = $this->resolveSessionName($sessionName)) {
            $sessions = $this->fetchSessionsStatus();
            $info = $sessions[$session];
            $section->overwrite(sprintf('Session <info>%s</info>: ' . "     \t" . '<comment>%s</comment>', $session, $info['Status']));
        };

        if($session) {
            $this->error(['Mutagen session <info>%s</info> appears to be gone...'], [$session]);
        } else {
            $this->error(['No Mutagen session matches <info>%s</info>'], [$sessionName]);
        }
    }

    protected function resolveSessionName($sessionName)
    {
        $sessions = $this->fetchSessionsStatus();

        foreach ($sessions as $session => $info) {
            if (preg_match('/' . str_replace('*', '.*', $sessionName) . '/', $session)) {
                return $session;
            }
        }

        return null;
    }
}
