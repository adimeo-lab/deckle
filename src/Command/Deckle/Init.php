<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Init extends AbstractDeckleCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('init')
            ->setDescription('Process configuration files')
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Overwrite previously processed files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if (!is_dir('./deckle')) {
            throw new DeckleException('No "./deckle" folder found. You may need to bootstrap your project.');
        }

        $type = $this->projectConfig['project']['type'];

        $initCommand = $type . ':init';
        $command = $this->getApplication()->find($initCommand);

        if(!$command) {
            throw new DeckleException('Unable to init "' . $type . '"  projects.');
        }

        $command->setProjectConfig($this->getProjectConfig());
        $arguments = [
            'command' => $initCommand
        ];

        $input = new ArrayInput($arguments);
        $command->run($input, $output);

        /*
        $deckleDirectory = new \RecursiveDirectoryIterator('./deckle');
        $deckleFiles = new \RecursiveIteratorIterator($deckleDirectory);
        $output->writeln('<info>Listing files in ' . $deckleDirectory->getPath() . '</info>');
        */
        /** @var \SplFileInfo $deckleFile */
        /*
        foreach ($deckleFiles as $deckleFile) {
            if ($deckleFile->isDir() || strpos($deckleFile->getPath(), '.template')) {
                if($output->isVerbose()) {
                    $output->writeln('Skipping <comment>' . $deckleFile->getPathname() . '</comment>');
                }
                continue;
            }

            $output->writeln($deckleFile->getPath());
        }
        */
    }

}
