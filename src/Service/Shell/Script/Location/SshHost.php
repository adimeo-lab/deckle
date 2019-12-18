<?php


namespace Adimeo\Deckle\Service\Shell\Script\Location;


class SshHost extends AbstractLocation
{
    /**
     * @var string
     */
    protected $host;
    /**
     * @var string
     */
    protected $user;
    /**
     * @var int
     */
    protected $port;

    /**
     * Host constructor.
     * @param string $path
     * @param string $host
     * @param string $user
     * @param int $port
     */
    public function __construct(string $host, string $path, string $user = null, int $port = 22)
    {
        $this->path = $path;
        $this->host = $host;
        $this->user = $user;
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * @param string $user
     * @return DeckleMachine
     */
    public function setUser(string $user)
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return DeckleMachine
     */
    public function setPort(int $port)
    {
        $this->port = $port;
    }

    public function getFullyQualifiedPath()
    {
        $fqp = '';
        if ($this->getUser()) {
            $fqp .= $this->getUser() . '@';
        }
        $fqp .= $this->getHost();
        $fqp .= ':' . $this->getPath();

        return $fqp;
    }

}
