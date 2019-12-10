<?php


namespace Adimeo\Deckle\Service\Placeholder;


class PlaceholdersManager
{
    public function extractPlaceholders($content) : array
    {
        $placeholders = [];
        preg_match_all('/(\w+)<(.*?)>/', $content, $matches);

        for($i=0; $i < count($matches[0]); $i++) {

            $rawPlaceholder = $matches[0][$i];
            $type = $matches[1][$i];
            $rawParams = explode(',', $matches[2][$i]);
            $params = [];
            foreach($rawParams as $rawParam) {
                $parts = explode('=', trim($rawParam), 2);
                if(count($parts) == 1) {
                    $params[] = trim($parts[0]);
                } else {
                    $params[trim($parts[0])] = trim($parts[1]);
                }
            }

            // prevent duplicates
            if(isset($placeholders[$rawPlaceholder])) {
                continue;
            }
            $placeholders[$rawPlaceholder] = new Placeholder($rawPlaceholder, $type, $params);
        }

        return $placeholders;

    }

    public function substitutePlaceholder(string $source, PlaceholderInterface $placeholder, $substitutedValue)
    {
        return str_replace($placeholder->getRaw(), $substitutedValue, $source);
    }
}
