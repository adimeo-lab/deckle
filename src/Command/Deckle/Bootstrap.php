<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Helper\ConfigHelper;
use Adimeo\Deckle\Command\Helper\TemplatesHelper;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Service\Config\DeckleConfig;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;

class Bootstrap extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{

    use TemplatesHelper;


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
                $template = $this->output->askQuestion(new Question('Please indicate which template to use (press enter to list available templates)'));

                if (!$template) {
                    $command = $this->getApplication()->find('templates:list');
                    $command->run(new ArrayInput([]), $output);
                }
            }

            $this->importTemplate($template);

        } catch (\Throwable $e) {

            $this->error($e->getMessage());
        }
    }

    protected function importTemplate($template)
    {
        $fs = new Filesystem();
        $provider = $this->templates()->resolveTemplateProvider($template);

        if (!$this->output->confirm('Are you sure you want to bootstrap your deckle project using <comment>' . $template . '</comment> from <comment>' . $provider . '</comment>')) {
            $this->output->writeln('<info>Aborting</info>');
            return;
        } else {
            if (is_dir('./deckle')) {

                if ($this->input->isInteractive() && !$this->input->getOption('reset')) {
                    $reset = $this->output()->confirm('<comment>./deckle</comment> directory already exists. Do you want to <comment>reset</comment> it using selected template?', false);
                } else {
                    $reset = $this->input->getOption('reset');
                }

                if ($reset) {
                    $fs->remove('./deckle');
                } else {
                    $this->output->writeln('<info>Bootstrap aborted by user because of an existing deckle installation.</info>');
                    exit;
                }
            }
        }

        mkdir('./deckle/.template', 0755, true);
        try {
            if ($this->output->isVerbose()) {
                $this->output->writeln('Copying template <info>' . str_replace($this->fs()->expandTilde('~'),
                        '~', $this->templates()->resolveTemplatePath($template,
                            $provider)) . '</info> to <comment>deckle project directory</comment> (' . realpath('./deckle/.template') . ')');
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
                        if ($this->output->isVerbose()) {
                            $this->output->writeln(sprintf('Creating target directory "<info>%s</info>"',
                                $targetDirectory));
                        }
                        mkdir($targetDirectory, 0755, true);
                    }

                    if ($this->output->isVerbose()) {
                        $this->output->writeln(sprintf('Copying "<info>%s</info>" to "<info>%s</info>"', $source,
                            $targetFile));
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
            $this->output->success([
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
