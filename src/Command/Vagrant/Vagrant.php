<?php


namespace Adimeo\Deckle\Command\Vagrant;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
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
        Deckle::print('Running <info>%s</info> in <info>%s</info>', [$cmd, $path]);
        $return = $this->sh()->exec($cmd, new LocalPath($path), false);
        if($return->isErrored()) {
            Deckle::warning([
                'The Deckle Machine may not have been installed using Vagrant, or is not installed',
                'in the default ~/.deckle/vagrant directory.',
                'You may need to reinstall Deckle Machine using "deckle install".'
            ]);
            Deckle::print(PHP_EOL . 'Vagrant command output: ' . PHP_EOL);
        }

        return $return->getReturnCode();
    }

}
