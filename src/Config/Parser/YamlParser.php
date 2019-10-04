<?php


namespace Adimeo\Deckle\Config\Parser;

use Adimeo\Deckle\Exception\Config\ConfigException;
use Symfony\Component\Yaml\Yaml;

class YamlParser implements ConfigParserInterface
{
    public function parse(string $configFile): array
    {
        return Yaml::parse($configFile);
    }


}
