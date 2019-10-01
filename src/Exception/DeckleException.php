<?php


namespace Adimeo\Deckle\Exception;


use Throwable;

class DeckleException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if(is_array($message))
        {
            $messageTemplate = array_shift($message);
            $message = vsprintf($messageTemplate, $message);
        }
        parent::__construct($message, $code, $previous);
    }

}
