<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Exception\DeckleException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Overwrite previously processed files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if (!is_dir('./deckle')) {
            $this->error('No "./deckle" folder found. You may need to bootstrap your project.');
        }

        $type = $this->projectConfig['project']['type'];

        $initCommand = $type . ':init';
        $command = $this->getApplication()->find($initCommand);

        if(!$command) {
            $this->error('Unable to init "%s" projects.', [$type]);
        }


        // process template before triggering project specific init process
        $templateContent = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('./deckle/.template'));

        while($templateContent->valid()) {

            if (!$templateContent->isDot()) {

                $source = $templateContent->key();
                $targetDirectory = './deckle/' . $templateContent->getSubPath();
                $targetFile = './deckle/' . $templateContent->getSubPathName();

                if($this->output->isVerbose()) $this->output->writeln(sprintf('Creating target directory "<info>%s</info>"', $targetDirectory));
                if(!is_dir($targetDirectory)) mkdir($targetDirectory, 0755, true);

                if($this->output->isVerbose()) $this->output->writeln(sprintf('Copying "<info>%s</info>" to "<info>%s</info>"', $source, $targetFile));
                $fileInfo = new \SplFileInfo($source);
                // return mime type ala mimetype extension
                $finfo = finfo_open(FILEINFO_MIME);
                //check to see if the mime-type starts with 'text'
                $binary = substr(finfo_file($finfo, $source), 0, 4) != 'text';
                if(!$binary) {
                    $this->copyTemplateFile($source, $targetFile, true,
                        ['conf<project.name>', 'conf<app.port>']);
                } else {
                    copy($source, $targetFile);
                }


            }

            $templateContent->next();
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
