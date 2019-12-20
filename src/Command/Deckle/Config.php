<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DumpProjectConfig
 * @package Adimeo\Deckle\Command\Deckle
 */
class Config extends AbstractDeckleCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this->setName("dump")
            ->setDescription("Dump current Deckle project configuration");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getConfig();
        $this->dumpArray($config->getConfigArray());
    }

    protected function dumpArray(array $array, int $shift = 0)
    {
        foreach ($array as $key => $value) {
            $spacer = str_repeat('   ', $shift);
            $writeFunction = is_array($value) ? 'writeLn' : 'write';
            Deckle::output()::$writeFunction($spacer . '<info>' . $key . '</info>: ');
            !is_array($value) ? Deckle::print('<comment>%s</comment>', $value) : $this->dumpArray($value, $shift + 1);
        }
    }

}
