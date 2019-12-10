<?php


namespace Misc;


use Adimeo\Deckle\Service\Misc\ArrayTool;
use PHPUnit\Framework\TestCase;

class ArrayToolTest extends TestCase
{

    public function testListKeys()
    {
        $array = ['root' => '', 'a' => ['b' => []], 'x' => ['y' => ['z' => []]]];
        $this->assertEquals('root, a, a[b], x, x[y], x[y][z]', implode(', ', ArrayTool::listKeys($array)));
    }

}
