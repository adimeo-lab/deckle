<?php


namespace Adimeo\Deckle\Command\Mutagen;


use Adimeo\Deckle\Command\AbstractDeckleCommand;

class AbstractMutagenCommand extends AbstractDeckleCommand
{
    protected function isMutagenUp()
    {
        $return = $this->sh()->exec('mutagen project list deckle/mutagen.yml');

        return !$return->isErrored();
    }



    protected function fetchSessionsStatus()
    {
        $return = $this->sh()->exec('mutagen project list deckle/mutagen.yml');

        $output = $return->getOutput();

        $forwardingSessions = [];
        $syncSessions = [];

        $currentSection = null;
        $session = null;
        $parsingEndpoint = false;

        foreach ($output as $line) {

            // skipped deepest info
            if (strpos($line, "\t\t") === 0) {
                continue;
            }
            if ($line == "No sessions found") {
                continue;
            }
            if (!$line) {
                continue;
            }


            if ($line == 'Forwarding sessions:') {
                $currentSection = &$forwardingSessions;
                continue;
            }

            if ($line == 'Synchronization sessions:') {
                $currentSection = &$syncSessions;
                continue;
            }

            if (preg_match('/Name: (.*)/', $line, $matches)) {
                $currentSection[$matches[1]] = [];
                $session = &$currentSection[$matches[1]];
                continue;
            }

            if (preg_match('/^(\w*):$/', $line, $matches)) {
                $section = strtolower($matches[1]);
                if (!in_array($section, ['alpha', 'beta'])) {
                    continue;
                }
                $session[$section] = [];
                $parsingEndpoint = $section;
                continue;
            }

            if ($line == 'Beta:') {
                $session['beta'] = [];
                $parsingEndpoint = 'beta';
                continue;
            }

            if (preg_match('/^\-+$/', $line)) {
                $parsingEndpoint = false;
                continue;
            }

            if ($parsingEndpoint && !(strpos($line, "\t") === 0)) {
                $parsingEndpoint = false;
            }

            if ($parsingEndpoint) {
                [$info, $value] = explode(':', $line, 2);
                $session[$parsingEndpoint][trim($info)] = trim($value);
                continue;
            } else {
                if (strpos($line, "\t") === 0) {
                    continue;
                }
                [$info, $value] = explode(':', $line, 2);
                $session[trim($info)] = trim($value);
                continue;
            }


        }

        return $syncSessions;
    }
}
