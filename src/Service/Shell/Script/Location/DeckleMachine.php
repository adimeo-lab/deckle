<?php


namespace Adimeo\Deckle\Service\Shell\Script\Location;


class DeckleMachine extends SshHost
{
    /**
     * Host constructor.
     * @param string $path
     * @param string $host
     * @param string $user
     * @param int $port
     */
    public function __construct(string $path = '~', string $host = null, string $user = null, int $port = 22)
    {
       $this->path = $path;
       $this->host = $host;
       $this->user = $user;
       $this->port = $port;
    }

}
