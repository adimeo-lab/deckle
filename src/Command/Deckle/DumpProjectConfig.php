<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DumpProjectConfig
 * @package Adimeo\Deckle\Command\Deckle
 */
class DumpProjectConfig extends AbstractDeckleCommand
{
    /**
     *
     */
    protected function configure()
    {
        $this->setName("project:config:dump")
            ->setDescription("Dump project configuration")
            ->setAliases(['dump']);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getProjectConfig();
        dump($config);
    }

}
