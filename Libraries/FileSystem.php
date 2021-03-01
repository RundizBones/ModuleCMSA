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
     * Get base 64 string from file name.
     * 
     * @link https://stackoverflow.com/a/13758760/128761 Original source code.
     * @param string $filename The file name to get contents. This will be connect with root in constructor.
     * @return string Return base 64 file content or empty string if not found.
     */
    public function getBase64File(string $filename): string
    {
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
        return $this->root . DIRECTORY_SEPARATOR . $path;
    }// getFullPathWithRoot


    /**
     * Get file name with suffix.
     * 
     * Example: file name is `photo123.jpg`, suffix is `_thumbnail300` then it will be return `photo123_thumbnail300.jpg`.
     * 
     * @param string $filename The file name file extension. Can be full path or not.
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


    /**
     * Get file name without suffix.
     * 
     * Example: file name with suffix is `photo123_thumbnail300.jpg`, suffix is `_thumbnail300` the it will be return `photo123.jpg`.
     * 
     * @param string $filename The file name file extension. Can be full path or not.
     * @param string $suffix The suffix string.
     * @return string Return the original file name without suffix.
     */
    public function removeSuffixFileName(string $filename, string $suffix): string
    {
        $expFilename = explode('.', $filename);
        // set file extension.
        $fileExt = $expFilename[count($expFilename) - 1];
        // remove last array index
        unset($expFilename[count($expFilename) - 1]);
        // merge array to get file name without extension.
        $fileNameNoExt = implode('.', $expFilename);

        $replaced = preg_replace('/' . preg_quote($suffix, '/') . '$/', '', $fileNameNoExt);
        unset($fileNameNoExt);

        return $replaced . '.' . $fileExt;
    }// removeSuffixFileName


}
