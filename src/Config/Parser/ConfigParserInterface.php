<?php

namespace Adimeo\Deckle\Config\Parser;


use Adimeo\Deckle\Config\DeckleConfig;

interface ConfigParserInterface
{
    public function parse(string $config) : array;
}
