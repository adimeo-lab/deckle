<?php


namespace Adimeo\Deckle\Service\Filesystem;


/**
 * Trait FilesystemTrait
 * @package Adimeo\Deckle\Service\Filesystem
 */
trait FilesystemTrait
{
    /**
     * @return FilesystemService
     */
    public function fs(): FilesystemService
    {
        return $this->getContainer()->get(FilesystemService::class);
    }

}
