<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Helper\TemplatesHelper;
use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class Install extends AbstractDeckleCommand
{

    use TemplatesHelper;

    const DECKLE_HOME = '~/.deckle';

    /** @var OutputInterface */
    protected $output;

    /** @var InputInterface */
    protected $input;

    protected function configure()
    {
        $this->setName('install')
            ->setDescription('Create (or reset) default Deckle configuration and clone templates locally')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Should existing configuration be overwritten?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        $this->output = $output;
        $this->input = $input;
        $target = $this->getDeckleHomeDirectory();

        if (is_dir($target)) {
            $overwrite = false;
            if ($input->isInteractive() && !$input->getOption('reset')) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('<question>Deckle configuration folder "' . $target . '" already exists. Should it be overwritten? [y/N]</question>',
                    false);
                $overwrite = $helper->ask($input, $output, $question);
            } elseif ($input->getOption('reset')) {
                $overwrite = true;
            }

            if ($overwrite) {

                if ($input->getOption('verbose')) {
                    $output->writeln('<info>Deleting "' . $target . '" directory...');
                }
                $fs->remove($target);
                if (is_dir($target)) {
                    throw new DeckleException(['Failed deleting "%s"', $target]);
                }

            } else {
                $output->writeln('<comment>Cannot install Deckle because of a previous installation not having been cleared. Please use "--reset" switch if you want to force reinstallation.</comment>');
                return;
            }
        }
        mkdir($target);
        $fs->mirror('resources', $target);
        $cwd = getcwd();
        mkdir('cache');
        chdir('cache');
        $this->cacheTemplates();
        chdir('..');
        chdir($cwd);
    }


}
