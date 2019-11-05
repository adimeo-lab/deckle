<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Init extends AbstractDeckleCommand
{

    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Process configuration files')
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Overwrite previously processed files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_dir('./deckle')) {
            throw new DeckleException('No "./deckle" folder found. You may need to bootstrap your project.');
        }

        $deckleDirectory = new \RecursiveDirectoryIterator('./deckle');
        $deckleFiles = new \RecursiveIteratorIterator($deckleDirectory);
        $output->writeln('<info>Listing files in ' . $deckleDirectory->getPath() . '</info>');
        /** @var \SplFileInfo $deckleFile */
        foreach ($deckleFiles as $deckleFile) {
            if ($deckleFile->isDir() || strpos($deckleFile->getPath(), '.template')) {
                if($output->isVerbose()) {
                    $output->writeln('Skipping <comment>' . $deckleFile->getPathname() . '</comment>');
                }
                continue;
            }

            $output->writeln($deckleFile->getPath());
        }

    }

}
