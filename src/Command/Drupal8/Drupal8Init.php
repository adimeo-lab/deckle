<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Deckle;
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

        Deckle::print('Initializing Drupal 8 project <info>' . $this->config['project']['name'] . '</info>');

        $this->generateLocalSettings();

        return 0;

    }

    protected function generateLocalSettings()
    {
        Deckle::runCommand('drupal8:gls');
    }


}
