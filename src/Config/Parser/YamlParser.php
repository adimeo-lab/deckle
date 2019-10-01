<?php


namespace Adimeo\Deckle\Config\Parser;

use Symfony\Component\Yaml\Yaml;

class YamlParser implements ConfigParserInterface
{
    public function parse(string $config): array
    {

        $config = Yaml::parse($config);

        return $config;
    }

}
