<?php


namespace Adimeo\Deckle\Service\Git;


trait GitTrait
{

    /**
     * @return GitService
     */
    public function git(): GitService
    {
        return $this->getContainer()->get(GitService::class);
    }

}
