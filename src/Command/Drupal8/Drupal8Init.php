<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
        if (file_exists('web/sites/default/settings.local.php') && $input->getOption('no-interaction') && !$input->getOption('reset')) {
            return 0;
        }

        $output->writeln('Initializing Drupal 8 project <comment>' . $this->config['project']['name'] . '</comment>');

        $this->generateLocalSettings();

        return 0;

    }

    protected function generateLocalSettings()
    {

        if ($this->output->confirm('Do you want to generate your "local.settings.php" file?')) {
            if ($this->runCommand('drupal8:generate:settings') === 0) {
                $this->output->writeln('<info>done...</info>');
            } else {
                $this->output->writeln('<error>failed...</error>');
            }
        }else {
            $this->output->writeln('<comment>skipped...</comment>');
        }
    }

    protected function runCommand($commandName, $arguments = [])
    {
        $command = $this->getApplication()->find($commandName);
        if($command) {
            if($command instanceof AbstractDeckleCommand && $this->hasConfig()) $command->setConfig($this->getConfig());
            $input = new ArrayInput($arguments);
            $input->setInteractive(!$this->input->getOption('no-interaction'));
            return $command->run($input, $this->output);
        } else {
            $this->error('Command "' . $commandName . '" not found');
        }
    }

}
