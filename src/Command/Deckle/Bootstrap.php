<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Helper\ConfigHelper;
use Adimeo\Deckle\Command\Helper\TemplatesHelper;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;

class Bootstrap extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{

    protected function configure()
    {

        $this->setName('bootstrap')
            ->setDescription('Prepare a project to run with Deckle')
            ->addOption('reset', null, InputOption::VALUE_NONE,
                'Clean any previous deckle project present in current directory. <info>Warning, you may loose data!</info>')
            ->addArgument('project', InputArgument::REQUIRED,
                'Project name. Will be used as db name, container name, etc.')
            ->addArgument('template', InputArgument::OPTIONAL,
                'Deckle template to use to bootstrap your project (syntax: vendor/template)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // reset config
        $config = [
            'project' =>
                [
                    'name' => $input->getArgument('project')
                ]
        ];
        $this->getConfig()->hydrate($config);

        // import template into project
        try {
            $template = $input->getArgument('template');

            while (!$template) {
                $template = Deckle::prompt('Please indicate which template to use (press enter to list available templates)');

                if (!$template) {
                   Deckle::runCommand('templates:list');
                }
            }

            $this->importTemplate($template);

        } catch (\Throwable $e) {

            Deckle::error($e->getMessage());
        }
    }

    protected function importTemplate($template)
    {
        $fs = new Filesystem();
        $provider = $this->templates()->resolveTemplateProvider($template);

        if (!Deckle::confirm('Are you sure you want to bootstrap your deckle project using <comment>' . $template . '</comment> from <comment>' . $provider . '</comment>')) {
            Deckle::print('<info>Aborting</info>');
            return;
        } else {
            if (is_dir('./deckle')) {

                if ($this->input->isInteractive() && !$this->input->getOption('reset')) {
                    $reset = Deckle::confirm('<comment>./deckle</comment> directory already exists. Do you want to <comment>reset</comment> it using selected template?',
                        false);
                } else {
                    $reset = $this->input->getOption('reset');
                }

                if ($reset) {
                    $fs->remove('./deckle');
                } else {
                    Deckle::print('<info>Bootstrap aborted by user because of an existing deckle installation.</info>');
                    exit;
                }
            }
        }

        mkdir('./deckle/.template', 0755, true);
        try {
            if (Deckle::isVerbose()) {
                $templateDisplayableName = str_replace($this->fs()->expandTilde('~'),
                    '~', $this->templates()->resolveTemplatePath($template,
                        $provider));

                Deckle::print('Copying template <info>%s</info> to <info>deckle project directory</info> (%s)', [$templateDisplayableName, realpath('./deckle/.template')]);
            }

            $fs->mirror($this->templates()->resolveTemplatePath($template, $provider), './deckle/.template');

            // process template files

            // process template before triggering project specific init process
            $templateContent = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('./deckle/.template'));

            while ($templateContent->valid()) {

                if (!$templateContent->isDot()) {

                    $source = $templateContent->key();
                    $targetDirectory = './deckle/' . $templateContent->getSubPath();
                    $targetFile = './deckle/' . $templateContent->getSubPathName();


                    if (!is_dir($targetDirectory)) {
                        if (Deckle::isVerbose()) {
                            Deckle::print('Creating target directory "<info>%s</info>"', $targetDirectory);
                        }
                        mkdir($targetDirectory, 0755, true);
                    }

                    if (Deckle::isVerbose()) {
                        Deckle::print('Copying "<info>%s</info>" to "<info>%s</info>"', [$source, $targetFile]);
                    }

                    // return mime type ala mimetype extension
                    $finfo = finfo_open(FILEINFO_MIME);
                    //check to see if the mime-type starts with 'text'
                    $binary = substr(finfo_file($finfo, $source), 0, 4) != 'text';
                    if (!$binary) {
                        $this->copyTemplateFile($source, $targetFile, true,
                            ['conf<project.name>']);
                    } else {
                        copy($source, $targetFile);
                    }

                }

                $templateContent->next();
            }


            file_put_contents('deckle/.template/.deckle.lock', $provider . ':' . $template);
            Deckle::success([
                'Done importing template!',
                'You should now adapt config in "./deckle/deckle.yml',
                'or create a "./deckle.local.yml" file to tune the default config.',
                'You can now launch the project by executing "deckle up".'
            ]);

        } catch (\Throwable $e) {
            //clean aborted installation in case of error
            $fs->remove('./deckle');
            throw $e;
        }

    }

}
