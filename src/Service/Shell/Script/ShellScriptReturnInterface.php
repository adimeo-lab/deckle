<?php


namespace Adimeo\Deckle\Service\Shell\Script;


interface ShellScriptReturnInterface
{
    public function getReturnCode() : int;

    public function getOutput() : array;

    public function __toString();

    public function isErrored(): bool;
}
