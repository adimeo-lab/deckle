<?php


namespace Adimeo\Deckle\Service\Config;


use Adimeo\Deckle\Config\DeckleConfig;
use Adimeo\Deckle\Config\Loader\FileLoader;
use Adimeo\Deckle\Config\Parser\YamlParser;
use Adimeo\Deckle\Config\Processor\ProcessorInterface;

class ConfigManager
{

    /** @var FileLoader */
    protected $loader;

    /** @var YamlParser */
    protected $parser;

    /** @var ProcessorInterface[] */
    protected $processors = [];

    /**
     * ConfigManager constructor.
     * @param FileLoaderder $loader
     * @param YamlParser $parser
     */
    public function __construct(FileLoader $loader, YamlParser $parser)
    {
        $this->loader = $loader;
        $this->parser = $parser;
    }

    public function load($configFilePath)
    {
        $configSource = $this->loader->load($configFilePath);

        return new DeckleConfig($this->parser->parse($configSource));
    }

}
