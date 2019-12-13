<?php


namespace Adimeo\Deckle\Command\Vagrant;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Vagrant extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{
    protected function configure()
    {
        $this->setName('vagrant')
            ->setDescription('Manage Deckle Vagrant VM')
        ->addArgument('cmd', InputArgument::REQUIRED, 'Main Vagrant command')
        ->addArgument('args', InputArgument::IS_ARRAY, 'Vagrant command parameters');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmd = $input->getArgument('cmd') . ' ' . implode(' ', $input->getArgument('args'));
        $path = $this->expandTilde('~/.deckle/vagrant');
        if(is_dir($path)) {
            chdir($path);
            $this->call('vagrant ' . $cmd);
        } else {
            $this->error('The Deckle VM may not have been installed using Vagrant, or is not installed in the default ~/.deckle/vagrant directory. You may need to reinstall Deckle VM using <info>deckle install</info>.');
        }
    }

}
