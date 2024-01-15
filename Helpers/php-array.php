<?php
/** 
 * Array helpers in case that PHP does not supported.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


if (!function_exists('array_is_list')) {
    /**
     * Checks whether a given array is a list
     * 
     * @link https://www.php.net/manual/en/function.array-is-list.php#127044 Original source code.
     * @link https://www.php.net/manual/en/function.array-is-list.php PHP document.
     * @link https://3v4l.org/paaK1 Benchmark result.
     * @param array $arr The array being evaluated.
     * @return bool Returns `true` if array is a list, `false` otherwise.
     */
    function array_is_list(array $array): bool
    {
        $i = -1;
        foreach ($array as $k => $v) {
            ++$i;
            if ($k !== $i) {
                return false;
            }
        }

        return true;
    }// array_is_list
}


if (!function_exists('array_key_last')) {
    /**
     * Gets the last key of an array
     * 
     * @link https://www.php.net/manual/en/function.array-key-last.php#123016 Original source code.
     * @link https://www.php.net/manual/en/function.array-key-last.php PHP document.
     * @param array $array An array.
     * @return int|string|null Returns the last key of array if the array is not empty; `null` otherwise.
     */
    function array_key_last(array $array)
    {
        if (!is_array($array) || empty($array)) {
            return NULL;
        }
       
        return array_keys($array)[count($array)-1];
    }// array_key_last
}