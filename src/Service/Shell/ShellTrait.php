<?php


namespace Adimeo\Deckle\Service\Shell;

/**
 * Trait ShellTrait
 * @package Adimeo\Deckle\Service\Shell
 */
trait ShellTrait
{
    /**
     * @return ShellService
     */
    public function sh(): ShellService
    {
        return $this->getContainer()->get(ShellService::class);
    }
}
