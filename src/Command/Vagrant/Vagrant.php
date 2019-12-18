<?php


namespace Adimeo\Deckle\Command\Vagrant;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Service\Shell\Script\Location\LocalPath;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Vagrant extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{
    protected function configure()
    {
        $this->setName('vagrant')
            ->setDescription('Manage Deckle Vagrant VM')
        ->addArgument('cmd', InputArgument::OPTIONAL, 'Main Vagrant command')
        ->addArgument('args', InputArgument::IS_ARRAY, 'Vagrant command parameters');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmd = 'vagrant ' . $input->getArgument('cmd') . ' ' . implode(' ', $input->getArgument('args'));

        $path = '~/.deckle/vagrant';
        $return = $this->sh()->exec($cmd, new LocalPath($path), false);
        if($return->isErrored()) {
            $this->output->warning([
                'The Deckle Machine may not have been installed using Vagrant, or is not installed',
                'in the default ~/.deckle/vagrant directory.',
                'You may need to reinstall Deckle Machine using "deckle install".'
            ]);
            $this->output->writeln(PHP_EOL . 'Vagrant command output: ' . PHP_EOL);
        }

        $this->output->writeln($return->getOutput());

        return $return->getReturnCode();
    }

}
