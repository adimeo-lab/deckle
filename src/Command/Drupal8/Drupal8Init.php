<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class Drupal8Init extends AbstractDrupal8Command
{

    /**
     * @var \Symfony\Component\Console\Helper\QuestionHelper
     */
    protected $questionHelper;

    protected function configure()
    {
        parent::configure();

        $this->setName('drupal8:init')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Overwrite previously processed files')
            ->setDescription('Initialize development environment for Drupal 8 project')
            ->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // skip if already done, no-interaction flag is true but reset flag is false
        if(file_exists('web/sites/default/settings.local.php') && $input->getOption('no-interaction') && !$input->getOption('reset'))
        {
            return 0;
        }

        $output->writeln('Initializing Drupal 8 project <comment>' . $this->projectConfig['project']['name'] . '</comment>');

        $this->questionHelper = $this->getHelper('question');;
        $this->generateLocalSettings();

    }

    protected function generateLocalSettings()
    {
        $question = new ConfirmationQuestion('<question>Do you want to generate your local.settings.php file?</question> [Y/n]', 'y');
        $answer = $this->questionHelper->ask($this->input, $this->output, $question);

        if($answer) {
            $command = $this->getApplication()->find('drupal8:generate:settings');
            $command->setProjectConfig($this->getProjectConfig());
            $arguments = [
                'command' => 'drupal8:generate:settings'
            ];

            $input = new ArrayInput($arguments);
            $input->setInteractive(!$this->input->getOption('no-interaction'));
            $command->run($input, $this->output);
            $this->output->writeln('<info>done...</info>');
        } else {
            $this->output->writeln('<comment>skipped...</comment>');
        }
    }

}
