<?php
namespace Adimeo\Deckle\Command\Vm;

use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Start extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{
    protected function configure()
    {
        $this->setName('vm:start')
            ->setDescription('Start the VM');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->vm()->start();
        if(!$result) {
            Deckle::error('Unable to start your Deckle Machine.');
        }

        Deckle::print('Your Deckle Machine has been started');
    }

}
