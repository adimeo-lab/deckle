<?php


namespace Adimeo\Deckle\Service\Config\Parser;

use Symfony\Component\Yaml\Yaml;

class YamlParser implements ConfigParserInterface
{
    public function parse(string $configFile): array
    {
        return Yaml::parse($configFile);
    }


}
