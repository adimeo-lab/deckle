<?php


namespace Adimeo\Deckle\Command\Vm;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Ip extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{
    protected function configure()
    {
        $this->setName('vm:ip')
            ->setDescription('Display Deckle Machine IP address');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ip = $this->vm()->ip();
        if(!$ip) {
            Deckle::error('Unable to extract your Deckle Machine IP address from your /etc/hosts file. Please ensure have entry for <comment>deckle-vm</comment> in this file.');
        }

        Deckle::print(sprintf('Your Deckle Machine IP address is <info>%s</info>', $ip));
    }

}
