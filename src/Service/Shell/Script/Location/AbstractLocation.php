<?php


namespace Adimeo\Deckle\Service\Shell\Script\Location;


abstract class AbstractLocation implements ShellScriptLocationInterface
{
    /**
     * @var string
     */
    protected $path;


    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return AbstractLocation
     */
    public function setPath(string $path): AbstractLocation
    {
        $this->path = $path;
        return $this;
    }
}
