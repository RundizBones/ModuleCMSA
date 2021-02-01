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
     * Connect with root (on constructor) and get its full path.
     * 
     * @param string $path The path that was not included in root.
     * @return string Return full path that was connected with root.
     */
    public function getFullPathWithRoot(string $path): string
    {
        return $this->root . DIRECTORY_SEPARATOR . $path;
    }// getFullPathWithRoot


    /**
     * Get file name with suffix.
     * 
     * Example: file name is `photo123.jpg`, suffix is `_thumbnail300` then it will be return `photo123_thumbnail300.jpg`.
     * 
     * @param string $filename The file name with full path or not but this can included the file extension.
     * @param string $suffix The suffix string.
     * @return string Return the same as `$filename` input but add suffix before file extension.
     */
    public function getSuffixFileName(string $filename, string $suffix): string
    {
        $expFilename = explode('.', $filename);
        // set file extension.
        $fileExt = $expFilename[count($expFilename) - 1];
        // remove last array index
        unset($expFilename[count($expFilename) - 1]);
        // merge array to get file name without extension.
        $fileNameNoExt = implode('.', $expFilename);

        return $fileNameNoExt . $suffix . '.' . $fileExt;
    }// getSuffixFileName


}
