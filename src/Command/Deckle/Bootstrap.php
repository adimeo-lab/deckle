<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Helper\ConfigHelper;
use Adimeo\Deckle\Command\Helper\TemplatesHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class Bootstrap extends AbstractDeckleCommand
{

    use TemplatesHelper;


    protected function configure()
    {
        $this->setName('bootstrap')
            ->setDescription('Import template and configure your deckle project for development environment')
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
            while(!$template) {
                $helper = $this->getHelper('question');
                $question = new Question('<question>Please indicate which template to use. Press enter to list available templates.</question>',
                    null);
                $template = $helper->ask($input, $output, $question);

                if(!$template) {

                    $command = $this->getApplication()->find('templates:list');
                    $command->run($input, $output);
                }
            }
            $this->importTemplate($template);
        } catch (\Throwable $e) {
            // something went wrong, leave project folder untouched
            unlink('.deckle.env');
        }
    }

    protected function importTemplate($template)
    {
        $provider = $this->resolveTemplateProvider($template);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to bootstrap your deckle project using <comment>' . $template . '</comment> from <comment>' . $provider . '</comment>');
        if(!$helper->ask($this->input, $this->output, $question)) {
            $this->output->writeln('<info>Aborting</info>');
            return;
        } else {
            mkdir('./deckle');
            $template = new \RecursiveDirectoryIterator();
            foreach($template as $item) {

            }
        }

    }

}
