<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
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
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Should existing configuration be overwritten?');
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
            return 1;
        }

        try {
            $initCommand = 'install:' . $os;
            $command = $this->getApplication()->find($initCommand);
        }
        catch(\Throwable $e) {
            Deckle::error('Unsupported OS', [$os]);
            return 1;
        }


        $command->setConfig($this->getConfig());

        $command->run($input, $output);

        Deckle::success(
            [
            'Deckle has been successfully installed on your computer.',
            'You can open now an ssh session by using "deckle vm:ssh" (passwd: deckle)',
            'You should be able to test it by accessing http://portainer.deckle.local']
        );
    }

}
