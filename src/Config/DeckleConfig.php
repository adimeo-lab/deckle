<?php


namespace Adimeo\Deckle\Config;


class DeckleConfig
{

    /** @var array  */
    protected $config = [];

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
        array_walk_recursive($config, function($value, $key) { echo $key . ' => ' . $value . PHP_EOL; });
exit;
        $this->config = $config;
    }
}
