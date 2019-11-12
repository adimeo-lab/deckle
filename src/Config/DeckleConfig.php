<?php


namespace Adimeo\Deckle\Config;


class DeckleConfig implements \ArrayAccess
{

    /** @var array  */
    protected $config = [
        'project' => ['name' => null, 'type' => null],
        'docker' => ['host' => null, 'path' => null],
        'app' => ['container' => null, 'path' => null, 'port' => null],
        'db' => ['container' => null, 'port' => null, 'database' => null, 'user' => null, 'passwd' => null],
        'reference' => ['host' => null, 'user' => null, 'path' => null, 'db' => ['host' => null, 'database' => null, 'user' => null, 'passwd' => null]],
        'extra' => []
    ];

    /** @var array  */
    protected $processors = [];

    /**
     * DeckleConfig constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->hydrate($config);
    }

    public function hydrate(array $config)
    {
        $this->config = array_merge_recursive($this->config, $config);
    }

    public function offsetExists($offset)
    {
        return isset($this->config[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->config[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->config[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->config[$offset]);
    }


}
