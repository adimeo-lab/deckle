<?php


namespace Adimeo\Deckle\Command\Mutagen;


use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Monitor extends AbstractMutagenCommand
{
    protected function configure()
    {
        $this->setName('mutagen:monitor')
            ->setAliases(['monitor'])
            ->setDescription('Mutagen sessions monitor')
            ->addOption('update-frequency', 'f', InputOption::VALUE_OPTIONAL, 'Monitor refreshing frequency', 2)
            ->addOption('until-sync', 'u', InputOption::VALUE_NONE, 'Quit the monitor once all sessions are synced')
            ->addArgument('session', InputArgument::OPTIONAL, 'Specific session to monitor (wildcard possible)', '*');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if (!is_file('deckle/mutagen.yml')) {
            Deckle::error('No deckle/mutagen.yml file was found. Cannot monitor mutagen.');
        }

        if (!$this->isMutagenUp()) {
            Deckle::halt('Mutagen is not running. Please use "deckle sync start" to start the sync sessions.');
        }

        $untilSynced = $input->getOption('until-sync');
        $frequency = $input->getOption('update-frequency');
        $sessionsToMonitor = str_replace('*', '.*', $input->getArgument('session'));

        $sections = [];
        $firstLoop = true;

        while ($running = $this->isMutagenUp()) {
            $synced = [];
            $sessions = $this->fetchSessionsStatus();
            $matchingSessions = 0;

            // compute padding
            $padding = 0;
            $conflicted = false;
            foreach ($sessions as $session => $infos) {
                $padding = strlen($session) > $padding ? strlen($session) + 1 : $padding;
                if (isset($infos['conflicted'])) {
                    $conflicted = true;
                }
            }

            if ($firstLoop && $sessions) {
                $infoSection = Deckle::console()->section();
                $s = $frequency > 1 ? 's' : '';
                $message = 'Monitoring Mutagen (refresh every <info>' . $frequency . '</info> second' . $s . ')';
                $infoSection->overwrite(PHP_EOL . $message);
                $spacer = Deckle::console()->section()->writeln('');
                if ($conflicted) {
                    $infoSection->writeln('<error>One or more session is in conflict. You should restart and/or clear your sessions.</error>');
                }
            }

            foreach ($sessions as $session => $info) {
                if (!preg_match('/' . $sessionsToMonitor . '/', $session)) {
                    continue;
                }
                $matchingSessions++;
                $synced[$session] = $info['Status'] == 'Watching for changes';
                if (!isset($sections[$session])) {
                    $sections[$session] = Deckle::console()->section();
                    $sections[$session]->writeLn('Fetching session <info>' . $session . '</info> status...');
                }
                $section = $sections[$session];
                $style = isset($info['conflicted']) ? 'error' : 'info';
                $section->overwrite(sprintf('Session <%s>% -' . $padding . 's</%1$s>' . " => " . '<comment>%s</comment>',
                    $style, $session, $info['Status']));
            }

            if(!$sessions) {
                if ($firstLoop) {
                    $message = 'No active session to monitor';
                } else {
                    $message = 'No more active session';
                }
                Deckle::halt($message);
            }

            if ($matchingSessions === 0) {
                $noMatch = $firstLoop;
                break;
            }

            if ($untilSynced && count(array_filter($synced)) == count($synced)) {

                $message = PHP_EOL . '<info>All sessions are now synced. Exiting monitor...</info>';

                if (isset($footerSection)) {
                    $footerSection->overwrite($message);
                } else {
                    Deckle::print($message);
                }
                return 0;
            };

            if ($firstLoop) {
                $footerSection = Deckle::console()->section();
                if ($untilSynced) {
                    $message = 'Monitor will quit once session(s) are in sync.';
                } else {
                    $message = 'Hit Ctrl+C to quit monitor.';
                }
                $footerSection->overwrite(PHP_EOL . $message);
                $firstLoop = false;
            }
            sleep($frequency);
        }

        if ($noMatch) {
            Deckle::warning('No session matches requested pattern: "' . $input->getArgument('session') . '"');
            return 1;
        } else {
            Deckle::warning('No more session is running.');
        }
        return 0;
    }

}
