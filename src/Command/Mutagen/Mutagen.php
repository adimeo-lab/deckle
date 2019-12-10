<?php


namespace Adimeo\Deckle\Command\Mutagen;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Mutagen extends AbstractDeckleCommand
{
    protected function configure()
    {
        $this->setName('mutagen:sync')
            ->setAliases(['sync'])
        ->setDescription('Wrapper for mutagen')
        ->addArgument('cmd', InputArgument::REQUIRED, 'mutagen operation to execute');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!is_file('deckle/mutagen.yml')) {
            $this->error('No deckle/mutagen.yml file was found. Cannot control mutagen for this project.');
        }


        switch($cmd = $input->getArgument('cmd')) {


            case 'start':
                system('mutagen project start deckle/mutagen.yml');
                break;

            case 'stop':
            case 'terminate':
                system('mutagen project terminate deckle/mutagen.yml');
                break;

            case 'restart':
                system('mutagen project terminate deckle/mutagen.yml');
                system('mutagen project start deckle/mutagen.yml');
                break;

            case 'monitor':
            case 'status':
                system('mutagen monitor');
                break;

            default:
                $this->error('Unknown mutagen operation "%s"', [$cmd]);
                break;
        }
    }

}
