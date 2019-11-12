<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Helper\ConfigHelper;
use Adimeo\Deckle\Command\Helper\TemplatesHelper;
use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;

class Bootstrap extends AbstractDeckleCommand
{

    use TemplatesHelper;


    protected function configure()
    {

        $this->setName('bootstrap')
            ->setDescription('Import Deckle template in your development environment')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Clean any previous deckle project present in current directory. <info>Warning, you may loose data!</info>')
            ->addArgument('id', InputArgument::REQUIRED, 'Project Id. Will be injected in deckle.yml in conf\<project.id\> placeholders')
            ->addArgument('template', InputArgument::OPTIONAL,
                'Deckle template to use to bootstrap your project (syntax: vendor/template)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // generate .deckle.env
        // TODO handle case where .deckle.env already exists
        file_put_contents('.deckle.env', 'dev');

        $this->loadEnvironment();

        // import template into project
        try {
            $template = $input->getArgument('template');
            while (!$template) {
                $helper = $this->getHelper('question');
                $question = new Question('<question>Please indicate which template to use. Press enter to list available templates.</question>',
                    null);
                $template = $helper->ask($input, $output, $question);

                if (!$template) {

                    $command = $this->getApplication()->find('templates:list');
                    $command->run($input, $output);
                }
            }
            $this->importTemplate($template);
        } catch (\Throwable $e) {

            if ($this->output->isVerbose()) {
                $this->output->writeln('<error>An error occured:</error> ' . $e->getMessage());
            }
            // something went wrong, leave project folder untouched
            if(file_exists('./deckle.env')) unlink('.deckle.env');
        }
    }

    protected function importTemplate($template)
    {
        $fs = new Filesystem();
        $provider = $this->resolveTemplateProvider($template);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to bootstrap your deckle project using <comment>' . $template . '</comment> from <comment>' . $provider . '</comment> [Y/n]');
        if (!$helper->ask($this->input, $this->output, $question)) {
            $this->output->writeln('<info>Aborting</info>');
            return;
        } else {
            if (is_dir('./deckle')) {

                if ($this->input->isInteractive() && !$this->input->getOption('reset')) {
                    $helper = $this->getHelper('question');
                    $question = new ConfirmationQuestion('<info>./deckle</info> directory already exists. Do you want to <comment>replace</comment> it selected template ? [y/N]',
                        false);
                    $reset = $helper->ask($this->input, $this->output, $question);
                } else $reset = $this->input->getOption('reset');

                if ($reset) {
                    $fs->remove('./deckle');
                } else {
                    throw new DeckleException('Bootstrap aborted by user because of an existing deckle installation.');
                }
            }
        }

        mkdir('./deckle/.template', 0755, true);
        try {
            if ($this->output->isVerbose()) {
                $this->output->writeln('Copying template <info>' . str_replace($this->expandTilde('~'),
                        '~', $this->resolveTemplatePath($template,
                            $provider)) . '</info> to <comment>deckle project directory</comment> (' . realpath('./deckle/.template') . ')');
            }


            $fs->mirror($this->resolveTemplatePath($template, $provider), './deckle/.template');
            $fs->mirror($this->resolveTemplatePath($template, $provider), './deckle/');
            // pre-process deckle.yml
            file_put_contents('deckle/deckle.yml', str_replace('conf<project.id>'));
            $template = new \RecursiveDirectoryIterator('./deckle/');

            $this->output->writeln('Done importing template! You should now adapt config in <info>./deckle/deckle.yml</info> before generating the entire project config files by executing <info>deckle init</info>. Enjoy!');

        } catch (\Throwable $e) {
            //clean aborted installation in case of error
            $fs->remove('./deckle');
            throw $e;
        }

    }

}
