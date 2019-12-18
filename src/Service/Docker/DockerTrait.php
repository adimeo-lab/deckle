<?php


namespace Adimeo\Deckle\Service\Docker;

/**
 * Trait DockerTrait
 * @package Adimeo\Deckle\Service\Docker
 */
trait DockerTrait
{
    /**
     * @return DockerService
     */
    public function docker(): DockerService
    {
        return $this->getContainer()->get(DockerService::class);
    }
}
