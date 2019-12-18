<?php


namespace Adimeo\Deckle\Service\Shell\Script\Location;


class LocalPath extends AbstractLocation
{

    /**
     * LocalPath constructor.
     * @param string $workingDirectory
     */
    public function __construct(string $workingDirectory = '.')
    {
        $this->path = $workingDirectory;
    }

    public function getFullyQualifiedPath()
    {
        return realpath($this->getPath());
    }


}
