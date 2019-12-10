<?php


namespace Adimeo\Deckle\Service\Misc;


class NetTool
{

    static public function ping($host)
    {
        exec(sprintf('ping -c 1 %s', escapeshellarg($host)), $output, $result);

        return $result === 0;
    }
}
