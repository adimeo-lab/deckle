<?php


namespace Adimeo\Deckle\Command\Php;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Service\Shell\Script\Location\Container;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Composer extends AbstractDeckleCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('php:composer')
            ->setAliases(['composer']);
        $this->addArgument('args', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Composer arguments');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $path = $this->config['app']['path'];
        $args = $this->input->getArgument('args');

        switch($args[0] ?? false) {

            case 'clear':

                //$command = 'rm -rf web/core/* ; rm -rf vendor/* ; rm -rf web/modules/contrib/*';
                $command = 'rm -rf ';
                $args = ['*'];
                $this->sh()->exec($command . implode(' ',  $args), new Container($this->config['app']['path'] . '/web/core'));
                $args = ['*'];
                $this->sh()->exec($command . implode(' ',  $args), new Container($this->config['app']['path'] . '/vendor'));
                $args = ['*'];
                $this->sh()->exec($command . implode(' ',  $args), new Container($this->config['app']['path'] . '/web/modules/contrib'));
                break;

            default:
                Deckle::print('Executing <comment>composer</comment> on remote container');
                $this->sh()->exec('composer ' . implode(' ', $args), new Container($path));
                break;
        }

    }
}
