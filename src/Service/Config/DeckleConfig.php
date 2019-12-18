<?php


namespace Adimeo\Deckle\Service\Config;


use Adimeo\Deckle\Exception\DeckleException;
use Adimeo\Deckle\Service\Misc\ArrayTool;


/**
 * Class DeckleConfig
 * @package Adimeo\Deckle\Config
 */
class DeckleConfig implements \ArrayAccess
{

    /** @var array  */
    protected $schema = [
        'project' => ['name' => null, 'type' => null],
        'plugins' => [
            'paths' => []
        ],
        'vm' => ['host' => null, 'user' => null],
        'docker' => ['host' => null, 'path' => null],
        'app' => ['container' => null, 'path' => null, 'port' => null, 'service' => null],
        'db' => ['container' => null, 'port' => null, 'database' => null, 'username' => null, 'password' => null],
        'reference' => ['host' => null, 'user' => null, 'path' => null, 'db' => ['host' => null, 'port' => '', 'database' => null, 'username' => null, 'password' => null]],
        'providers' => [],
        'extra' => []
    ];

    protected $config = [];

    /**
     * DeckleConfig constructor.
     * @param array $data
     * @throws DeckleException
     */
    public function __construct(array $data = [])
    {
        $this->config = $this->schema;

        $this->hydrate($data);

        $data = ArrayTool::filterRecursive($data);

        if($data) {
            throw new DeckleException(['Some unexpected configuration directives (%s) were not used to hydrate DeckleConfig.', implode(', ', ArrayTool::listKeys($data))]);
        }

    }

    /**
     * @param array $data
     * @param array $config
     * @param null $schema
     * @throws DeckleException
     */
    public function hydrate(array &$data, array &$config = null, $schema = null)
    {
        if(is_null($config)) $config = &$this->config;

        if(is_null($schema)) $schema = $this->schema;

        foreach ($schema as $item => $value)
        {
            // skip values not present in $data
            if(!isset($data[$item])) continue;

            // empty arrays in schema are accepted as is - e.g. "extra"
            if(is_array($value) && empty($value)) {
                $config[$item] = $data[$item];
                unset($data[$item]);
            }
            // sub array to hydrate...
            elseif(is_array($value)) {
                $this->hydrate($data[$item], $config[$item], $schema[$item]);
            }
            // scalar values
            elseif (is_null($value)) {
                if(is_scalar($data[$item])) {
                    $config[$item] = $data[$item];
                    unset($data[$item]);
                } else {
                    throw new DeckleException(['An error occurred while handling "%s" config entry: a scalar value is expected, non-scalar value was passed.', $item]);
                }
            }
        }

        // check values
        $this->checkConfig();
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->config[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->config[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws DeckleException
     */
    public function offsetSet($offset, $value)
    {
        $this->config[$offset] = $value;
        $this->checkConfig();
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->config[$offset]);
    }

    /**
     * @return array
     */
    public function getConfigArray() : array
    {
        return $this->config;
    }

    public function get($key, $default = null)
    {
        $config = &$this->config;
        $parts = explode('.', $key);
        for($i=0; $i < count($parts) - 1; $i++) {
            if(!isset($config[$parts[$i]]) || !is_array($config[$parts[$i]])) {
                return $default;
            }
            $config = &$config[$parts[$i]];
        }

        return $config[$parts[$i]] ?? $default;

    }

    private function checkConfig()
    {
        // project name
        $projectName = $this->get('project.name');
        if($projectName && !preg_match('/^[\w\d]+$/', $projectName)) {
            throw new DeckleException(['Project name "%s" is invalid: it may contains only alphanumerical characters', $projectName]);
        }
    }
}
