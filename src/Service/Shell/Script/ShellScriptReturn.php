<?php

declare(strict_types=1);

namespace Adimeo\Deckle\Service\Shell\Script;


/**
 * Class ShellScriptReturn
 * @package Adimeo\Deckle\Service\Shell\Script
 */
class ShellScriptReturn implements ShellScriptReturnInterface
{

    /**
     * @var int
     */
    protected $returnCode;

    /**
     * @var array
     */
    protected $output;

    /**
     * ShellScriptReturn constructor.
     * @param int $returnCode
     * @param array $output
     */
    public function __construct(int $returnCode, array $output)
    {
        $this->returnCode = $returnCode;
        $this->output = $output;
    }


    /**
     * @return int
     */
    public function getReturnCode(): int
    {
        return $this->returnCode;
    }

    /**
     * @return array
     */
    public function getOutput(): array
    {
        return $this->output;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return implode(PHP_EOL, $this->getOutput());
    }

    public function isErrored() : bool {
        return (bool) $this->returnCode;
    }
}
