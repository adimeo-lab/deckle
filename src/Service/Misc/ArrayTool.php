<?php


namespace Adimeo\Deckle\Service\Misc;


class ArrayTool
{
    public static function filterRecursive($array, callable $callback = null)
    {
        foreach ($array as $index => $value) {
            if (is_array($value)) {
                $filteredValue = self::filterRecursive($value, $callback);
            } else {
                $filteredValue = $callback ? $callback($value, $index) : $value;
            }

            if ($filteredValue) {
                $array[$index] = $filteredValue;
            } else {
                unset($array[$index]);
            }
        }
        return $array;
    }

    public static function listKeys(array $array, &$keys = [], $prefix = null)
    {
        foreach ($array as $key => $value) {
            $currentKey = $prefix ? $prefix . '[' . $key . ']' : $key;
            $keys[] = $currentKey;
            if (is_array($value)) {
                self::listKeys($value, $keys, $currentKey);
            }
        }

        return $keys;
    }
}
