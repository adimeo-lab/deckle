<?php


namespace Adimeo\Deckle\Service\Shell\Script\Location;


class Container extends AbstractLocation
{
    /**
     * @var string host where to look container for
     */
    protected $dockerHost;

    /**
     * Container constructor.
     * @param $name
     * @param string $workingDirectory
     * @param string $dockerHost
     */
    public function __construct(string $name, string $workingDirectory, string $dockerHost = null)
    {
        $this->setPath($workingDirectory);
        $this->setName($name);
        if($dockerHost) $this->setDockerHost($dockerHost);
    }

    /**
     * @var string
     */
    protected $name;

    /**
     * @return string
     */
    public function getDockerHost(): string
    {
        return $this->dockerHost;
    }

    /**
     * @param string $dockerHost
     * @return Container
     */
    public function setDockerHost(string $dockerHost): Container
    {
        $this->dockerHost = $dockerHost;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return Container
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }


    /**
     * Return Mutagen style docker path
     *
     * Caution: this format relies on DOCKER_HOST environment variable to be
     * actually fully qualified (no host in docker://...)
     *
     * @return string
     */
    public function getFullyQualifiedPath()
    {
        return $this->getName() . ':' . ltrim($this->getPath());
    }


}
