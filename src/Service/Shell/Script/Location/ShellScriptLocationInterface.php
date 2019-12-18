<?php


namespace Adimeo\Deckle\Service\Shell\Script\Location;


interface ShellScriptLocationInterface
{
    public function getPath() : string;

    public function getFullyQualifiedPath();
}
