<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Exception\DeckleException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Install extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{

    const DECKLE_HOME = '~/.deckle';

    protected function configure()
    {
        $this->setName('install')
            ->setDescription('Install or reinstall Deckle environment and VM')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        switch(true) {
            case is_dir('/Applications'):
                $os = 'macos';
                break;

            case is_dir('/boot'):
                $os = 'linux';
                break;

            default:
                $os = 'unsupported';
                return 1;
        }

        $initCommand = 'install:' . $os;
        $command = $this->getApplication()->find($initCommand);

        if(!$command) {
            Deckle::error('Unsupported OS', [$os]);
            return 1;
        }


        $command->setConfig($this->getConfig());
        $arguments = [
            'command' => $initCommand
        ];

        $input = new ArrayInput($arguments);
        $input->setInteractive($this->input->isInteractive());
        $command->run($input, $output);

        Deckle::success([
            'Deckle has been successfully installed on your computer.',
            'You can open now an ssh session by using "deckle vm:ssh" (passwd: deckle)',
            'You should be able to test it by accessing http://portainer.deckle.local']);
    }

}
