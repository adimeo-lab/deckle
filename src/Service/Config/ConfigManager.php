<?php


namespace Adimeo\Deckle\Service\Config;


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
        $rawConfigs = $this->loader->load($configFilePath);

        return $this->parser->parse($rawConfigs);
    }

    public function merge(array $main, array $local)
    {
        $merged = $main;
        foreach ($local as $item => $value) {
            if (!isset($main[$item])) {
                $merged[$item] = $value;
            } else {

                if (is_scalar($value)) {
                    $merged[$item] = $value;
                    echo 'Importing ' . $item . ' in config ' . PHP_EOL;
                } else {
                    $merged[$item] = $this->merge((array)$main[$item], (array)$value);
                }
            }
        }

        return $merged;
    }
}
