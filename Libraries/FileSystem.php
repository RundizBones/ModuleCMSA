<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Libraries;


/**
 * Extended framework's `FileSystem` class.
 * 
 * @since 0.0.1
 */
class FileSystem extends \Rdb\System\Libraries\FileSystem
{


    /**
     * Get file name with suffix or add suffix to the selected file name.
     * 
     * Example: file name is `photo123.jpg`, suffix is `_thumbnail300` then it will be return `photo123_thumbnail300.jpg`.
     * 
     * @param string $filename The file name file extension. Can be full path or not.
     * @param string $suffix The suffix string.
     * @param bool $withDotRandomSuffix Set to `true` to add dot with random number before file extension. Default is `false` to not including it. This parameter is available since 0.0.6
     * @return string Return the same as `$filename` input but add suffix before file extension.
     */
    public function addSuffixFileName(string $filename, string $suffix, bool $withDotRandomSuffix = false): string
    {
        $expFilename = explode('.', $filename);
        // set file extension.
        $fileExt = $expFilename[count($expFilename) - 1];
        // remove last array index
        unset($expFilename[count($expFilename) - 1]);
        // merge array to get file name without extension.
        $fileNameNoExt = implode('.', $expFilename);

        if (true === $withDotRandomSuffix) {
            // if set to add dot random suffix.
            // generate dot random suffix. ( https://stackoverflow.com/a/8216031/128761 original source code. )
            $digits = 6;
            $randomSuffix = '.' . mt_rand(pow(10, $digits-1), pow(10, $digits)-1);
            unset($digits);
        } else {
            $randomSuffix = '';
        }

        return $fileNameNoExt . $suffix . $randomSuffix . '.' . $fileExt;
    }// addSuffixFileName


    /**
     * Get file name with suffix.
     * 
     * This is an alias method of `addSuffixFileName()`.<br>
     * This will be remove in version 1.0, please use `addSuffixFileName()` instead.
     * 
     * Example: file name is `photo123.jpg`, suffix is `_thumbnail300` then it will be return `photo123_thumbnail300.jpg`.
     * 
     * @deprecated 0.0.6 This will be remove on version 1.0.
     * @todo [rdbcms] Remove this method (`getSuffixFileName`) in version 1.0.
     * @param string $filename The file name file extension. Can be full path or not.
     * @param string $suffix The suffix string.
     * @return string Return the same as `$filename` input but add suffix before file extension.
     */
    public function getSuffixFileName(string $filename, string $suffix): string
    {
        return $this->addSuffixFileName($filename, $suffix);
    }// getSuffixFileName


    /**
     * Get file name without suffix.
     * 
     * Example: file name with suffix is `photo123_thumbnail300.jpg`, suffix is `_thumbnail300` the it will be return `photo123.jpg`.
     * 
     * @param string $filename The file name file extension. Can be full path or not.
     * @param string $suffix The suffix string.
     * @param bool $withDotRandomSuffix Set to `true` to use dot with random number before file extension. Default is `false` to not including it. This parameter is available since 0.0.6
     * @return string Return the original file name without suffix.
     */
    public function removeSuffixFileName(string $filename, string $suffix, bool $withDotRandomSuffix = false): string
    {
        $expFilename = explode('.', $filename);
        // set file extension.
        $fileExt = $expFilename[count($expFilename) - 1];
        // remove last array index
        unset($expFilename[count($expFilename) - 1]);
        // merge array to get file name without extension.
        $fileNameNoExt = implode('.', $expFilename);
        
        if (true === $withDotRandomSuffix) {
            // if set to use dot random suffix.
            $randomSuffix = '(\.[0-9]{6})';
            unset($digits);
        } else {
            $randomSuffix = '';
        }

        $replaced = preg_replace('/' . preg_quote($suffix, '/') . $randomSuffix . '$/', '', $fileNameNoExt);
        unset($fileNameNoExt, $randomSuffix);

        return $replaced . '.' . $fileExt;
    }// removeSuffixFileName


}
