<?php


namespace Adimeo\Deckle\Service\Placeholder;


class Placeholder implements PlaceholderInterface
{
    /** @var string */
    protected $raw;

    /** @var int */
    protected $endOffset;

    /** @var string */
    protected $type;

    /** @var array */
    protected $params = [];

    /**
     * Placeholder constructor.
     * @param string $raw
     * @param string $type
     * @param array $params
     */
    public function __construct(string $raw, string $type, array $params = [])
    {
        $this->raw = $raw;
        $this->type = $type;
        $this->params = $params;
    }


    /**
     * @return int
     */
    public function getRaw(): string
    {
        return $this->raw;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
