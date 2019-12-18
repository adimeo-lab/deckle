<?php


namespace Adimeo\Deckle\Command\Mutagen;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Monitor extends AbstractMutagenCommand
{
    /**
     * @var OutputInterface
     */
    protected $actualOutput;

    protected function configure()
    {
        $this->setName('mutagen:monitor')
            ->setDescription('Mutagen sessions monitor')
            ->addOption('update-frequency', 'f', InputOption::VALUE_OPTIONAL, 'Monitor refreshing frequency', 2)
            ->addOption('until-sync', 'u', InputOption::VALUE_NONE, 'Quit the monitor once all sessions are synced')
            ->addArgument('session', InputArgument::OPTIONAL, 'Specific session to monitor (wildcard possible)', '*');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->actualOutput = $output;

        if (!is_file('deckle/mutagen.yml')) {
            $this->error('No deckle/mutagen.yml file was found. Cannot monitor mutagen.');
        }

        if (!$this->isMutagenUp()) {
            $this->halt('Mutagen is not running. Please use "deckle sync start" to start the sync sessions.');
        }

        $untilSynced = $input->getOption('until-sync');
        $frequency = $input->getOption('update-frequency');
        $sessionsToMonitor = str_replace('*', '.*', $input->getArgument('session'));

        $sections = [];
        $firstLoop = true;

        $consoleOutput = new ConsoleOutput();


        while ($running = $this->isMutagenUp()) {
            $synced = [];
            $sessions = $this->fetchSessionsStatus();
            $matchingSessions = 0;

            foreach ($sessions as $session => $info) {
                if (!preg_match('/' . $sessionsToMonitor . '/', $session)) {
                    continue;
                }
                $matchingSessions++;
                $synced[$session] = $info['Status'] == 'Watching for changes';
                if (!isset($sections[$session])) {
                    $sections[$session] = $consoleOutput->section();
                    $sections[$session]->writeLn('Fetching session <info>' . $session . '</info> status...');
                }
                $section = $sections[$session];
                $section->overwrite(sprintf('Session <info>%s</info>: ' . "     \t" . '<comment>%s</comment>',
                    $session, $info['Status']));
            }

            if ($untilSynced && count(array_filter($synced)) == count($synced)) {
                if($firstLoop) {
                    $message = '<error>No active session to monitor</error>';
                } else {
                    $message = PHP_EOL . '<info>All sessions are now synced. Exiting monitor...</info>';
                }
                if (isset($infoSection)) {
                    $infoSection->overwrite($message);
                } else {
                    $this->output->writeln($message);
                }
                return 0;
            };

            if ($matchingSessions === 0) {
                $noMatch = $firstLoop;
                break;
            }

            if ($firstLoop) {
                $infoSection = $consoleOutput->section();
                $message = 'Monitoring Mutagen sessions every <info>' . $frequency . '</info> second. ';
                if ($untilSynced) {
                    $message .= 'Monitor will quit once session(s) are in sync.';
                } else {
                    $message .= 'Hit Ctrl+C to quit monitor.';
                }
                $infoSection->overwrite(PHP_EOL . $message);
                $firstLoop = false;
            }


            sleep($frequency);
        }

        if ($noMatch) {
            $this->output->warning('No session matches requested pattern: "' . $input->getArgument('session') . '"');
            return 1;
        } else {
            $this->output->warning('No more session is running.');
        }
        return 0;
    }

}
