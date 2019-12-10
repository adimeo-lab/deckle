<?php


namespace Config;


use Adimeo\Deckle\Config\DeckleConfig;
use Codeception\PHPUnit\TestCase;

class DeckleConfigTest extends TestCase
{

    public function testHydration()
    {
        $config = new DeckleConfig($this->getConfig());
        $this->assertEquals('deckle-vm2', $config['vm']['host']);
        $this->assertEquals('overridden password', $config['reference']['db']['password']);
        $this->assertEquals(['what' => 'ever'], $config['extra']);
    }


    protected function getConfig() {
        return  [
            'vm' => [
                'host' => 'deckle-vm2'
            ],
            'reference' => [
                'db' => [
                    'password' => 'overridden password'
                ]
            ],
            'extra' => [
                'what' => 'ever'
            ]
        ];
    }
}
