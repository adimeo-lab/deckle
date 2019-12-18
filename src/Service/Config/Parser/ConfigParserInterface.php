<?php

namespace Adimeo\Deckle\Service\Config\Parser;


interface ConfigParserInterface
{
    public function parse(string $configFile) : array;
}
