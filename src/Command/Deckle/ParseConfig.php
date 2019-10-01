<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Config\Loader\FileLoader;
use Adimeo\Deckle\Config\Parser\YamlParser;
use Adimeo\Deckle\Service\Config\ConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseConfig extends Command
{

    /** @var ConfigManager */
    protected $configManager;


    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
        parent::__construct(null);
    }


    protected function configure(){
        $this->setName("config:parse")
            ->setDescription("Parse a deckle.yml file")
            ->setAliases(['cp'])
            ->addArgument('file', InputArgument::OPTIONAL, 'Config file path', 'deckle.yml');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $config = $this->configManager->load($input->getArgument('file'));


        dump($config);
    }

}
