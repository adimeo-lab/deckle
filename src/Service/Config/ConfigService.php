<?php


namespace Adimeo\Deckle\Service\Config;


use Adimeo\Deckle\Service\AbstractDeckleService;
use Adimeo\Deckle\Service\Config\Loader\FileLoader;
use Adimeo\Deckle\Service\Config\Parser\YamlParser;
use Adimeo\Deckle\Service\Config\Processor\ProcessorInterface;

class ConfigService extends AbstractDeckleService
{

    /** @var FileLoader */
    protected $loader;

    /** @var YamlParser */
    protected $parser;

    /** @var ProcessorInterface[] */
    protected $processors = [];

    /**
     * ConfigManager constructor.
     * @param FileLoader $loader
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
                } else {
                    $merged[$item] = $this->merge((array)$main[$item], (array)$value);
                }
            }
        }

        return $merged;
    }
}
