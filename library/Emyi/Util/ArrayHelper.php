<?php
/*
 * emyi
 *
 * @link http://github.com/douggr/emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Util;

/**
 * Array utils
 * I wish "array" was not a reserved word just like String is not :(
 */
class ArrayHelper {
    /**
     * Flatten an array
     *
     * @param array
     * @return array
     */
    function flatten(array $input)
    {
        $i = 0;

        while ($i < count($input)) {
            if (is_array($input[$i])) {
                array_splice($input, $i, 1, $input[$i]);
            } else {
                ++$i;
            }
        }

        return $input;
    }

    /**
     * Somewhat naive way to determine if an array is a hash.
     */
    function is_hash(&$input)
    {
        if (!is_array($input))
            return false;

        $keys = array_keys($input);

        return isset($keys[0]) && is_string($keys[0]);
    }
}
