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
            // generate dot random suffix.
            $digits = 6;
            $randomSuffix = '.' . mt_rand(pow(10, $digits-1), pow(10, $digits)-1);
            unset($digits);
        } else {
            $randomSuffix = '';
        }

        return $fileNameNoExt . $suffix . $randomSuffix . '.' . $fileExt;
    }// addSuffixFileName


    /**
     * Get base 64 string from file name.
     * 
     * @link https://stackoverflow.com/a/13758760/128761 Original source code.
     * @param string $filename The file name to get contents. This will be connect with root in constructor.
     * @return string Return base 64 file content or empty string if not found.
     */
    public function getBase64File(string $filename): string
    {
        $filename = $this->removeUpperPath($filename);

        if (is_file($this->root . DIRECTORY_SEPARATOR . $filename)) {
            $filePath = $this->root . DIRECTORY_SEPARATOR . $filename;
            $Finfo = new \finfo();
            $mimetype = $Finfo->file($filePath, FILEINFO_MIME_TYPE);
            unset($Finfo);
            $data = file_get_contents($filePath);
            return 'data:' . $mimetype . ';base64,' . base64_encode($data);
        }

        return '';
    }// getBase64File


    /**
     * Connect with root (on constructor) and get its full path.
     * 
     * @param string $path The path that was not included in root.
     * @return string Return full path that was connected with root.
     */
    public function getFullPathWithRoot(string $path): string
    {
        $path = $this->removeUpperPath($path);
        return $this->root . DIRECTORY_SEPARATOR . $path;
    }// getFullPathWithRoot


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
        unset($fileNameNoExt);

        return $replaced . '.' . $fileExt;
    }// removeSuffixFileName


}
