<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->setDescription('Initialize development environment for Drupal 8 project')
            ->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Initializing Drupal 8 project <comment>' . $this->projectConfig['project']['name'] . '</comment>');

        /*
        // push Docker config
        $command = $this->getApplication()->find('push');
        $command->setProjectConfig($this->getProjectConfig());
        $arguments = [
            'command' => 'push'
        ];

        $input = new ArrayInput($arguments);
        $command->run($input, $output);
        */

        $this->questionHelper = $this->getHelper('question');;
        $this->generateLocalSettings();

    }

    protected function generateLocalSettings()
    {

        $question = new Question('<question>Do you want to generate your local.settings.php file?</question> [Y/n]');
        $choice = $this->questionHelper->ask($this->input, $this->output, $question);

        if(empty($choice) || $choice == strtolower('y')) {
            // push Docker config
            $command = $this->getApplication()->find('drupal8:generate:settings');
            $command->setProjectConfig($this->getProjectConfig());
            $arguments = [
                'command' => 'drupal8:generate:settings'
            ];

            $input = new ArrayInput($arguments);
            $command->run($input, $this->output);
        }
    }

}
