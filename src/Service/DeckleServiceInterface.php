<?php


namespace Adimeo\Deckle\Service;


use Adimeo\Deckle\Service\Config\DeckleConfig;

interface DeckleServiceInterface
{
    public function setConfig(DeckleConfig $config);

    public function hasConfig() : bool;
}
