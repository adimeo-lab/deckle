<?php


namespace Adimeo\Deckle\Service\Vm;


/**
 * Trait VmTrait
 * @package Adimeo\Deckle\Service\Vm
 */
trait VmTrait
{
    /**
     * @return VmService
     */
    public function vm()
    {
        return $this->getContainer()->get(VmService::class);
    }
}
