<?php


namespace Adimeo\Deckle\Service\Placeholder;


interface PlaceholderInterface
{
    public function getRaw() : string;

    public function getType() : string;

    public function getParams() : array;
}
