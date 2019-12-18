<?php


namespace Adimeo\Deckle\Service\Config\Loader;


use Adimeo\Deckle\Exception\Config\ConfigException;

class FileLoader implements ConfigLoaderInterface
{
    public function load(string $config): string
    {

        if(!file_exists($config)) {
            throw new ConfigException(['Config file "%s" was not found', $config]);
        }

        if(!is_readable($config)) {
            throw new ConfigException(['Insufficient permission for reading config file "%s"', $config]);
        }

        return file_get_contents($config);

    }

}
