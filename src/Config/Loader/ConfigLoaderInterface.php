<?php


namespace Adimeo\Deckle\Config\Loader;


interface ConfigLoaderInterface
{
    public function load($config) : string;
}
