<?php
/** 
 * Array helpers in case that PHP does not supported.
 * @license http://opensource.org/licenses/MIT MIT
 */


if (!function_exists('array_key_last')) {
    /**
     * Gets the last key of an array
     * 
     * @link https://www.php.net/manual/en/function.array-key-last.php#123016 Original source code.
     * @param array $array An array.
     * @return int|string|null Returns the last key of array if the array is not empty; null otherwise.
     */
    function array_key_last(array $array) {
        if (!is_array($array) || empty($array)) {
            return NULL;
        }
       
        return array_keys($array)[count($array)-1];
    }
}