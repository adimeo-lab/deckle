<?php


namespace Adimeo\Deckle\Service\Shell\Script\Location;


class AppContainer extends Container
{
    public function __construct(string $workingDirectory, string $dockerHost = null)
    {
        $this->path = $workingDirectory;
        $this->dockerHost = $dockerHost;
    }

}
