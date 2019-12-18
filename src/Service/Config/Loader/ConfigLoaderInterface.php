<?php


namespace Adimeo\Deckle\Service\Config\Loader;


interface ConfigLoaderInterface
{
    public function load(string $config) : string;
}
