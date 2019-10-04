<?php

namespace Adimeo\Deckle\Config\Parser;


interface ConfigParserInterface
{
    public function parse(string $configFile) : array;
}
