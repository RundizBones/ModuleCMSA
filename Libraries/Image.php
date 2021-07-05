<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Libraries;


/**
 * Image manipulation class.
 * 
 * @since 0.1
 * @property-read string $driverUse The image driver that will be use. Value can be 'gd', 'imagick'.
 * @property-read \Rundiz\Image\Drivers\Gd|\Rundiz\Image\Drivers\Imagick $Image The image driver class.
 * @property-write \Rdb\System\Container $Container The container class.
 */
class Image
{


    /**
     * @since 0.0.8
     * @var \Rdb\System\Container|null
     */
    protected $Container;


    /**
     * @var string The image driver that will be use. Value can be 'gd', 'imagick'.
     */
    protected $driverUse;


    /**
     * @var \Rundiz\Image\Drivers\Gd|\Rundiz\Image\Drivers\Imagick
     */
    protected $Image;


    /**
     * Class constructor.
     * 
     * @param string $source_image_path Path to source image file.
     * @return \Rundiz\Image\Drivers\Gd|\Rundiz\Image\Drivers\Imagick
     */
    public function __construct(string $source_image_path)
    {
        $this->init($source_image_path);

        return $this->Image;
    }// __construct


    /**
     * Initialize the image class.
     * 
     * This is for manual re-initialize after `new Image()` is already called.
     * 
     * @since 0.0.8
     * @param string $source_image_path Path to source image file.
     */
    public function init(string $source_image_path)
    {
        if (extension_loaded('imagick') === true) {
            $this->Image = new \Rundiz\Image\Drivers\Imagick($source_image_path);
            $this->driverUse = 'imagick';
        } else {
            $this->Image = new \Rundiz\Image\Drivers\Gd($source_image_path);
            $this->driverUse = 'gd';
        }
    }// init


    /**
     * Magic get.
     * 
     * @param string $name Property name.
     * @return mixed Return property's value.
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
        return null;
    }// __get


    /**
     * Magic set.
     * 
     * @since 0.0.8
     * @param string $name Property name. Allowed named:<br>
     *              `Container`.<br>
     * @param mixed $value Property's value.
     */
    public function __set($name, $value)
    {
        if ($name === 'Container') {
            $this->Container = $value;
        }
    }// __set


    /**
     * Remove watermark from uploaded image.
     * 
     * This method will be make a copy of original image to main file on the same folder and then delete the original file.
     * 
     * To use this method, the `Container` property must be set.
     * 
     * @since 0.0.8
     * @param string $file The full path to main image file. The main image file is the file that displayed on the web pages 
     *              but it is not original uploaded file that was created before apply watermark.<br>
     * @param array $options Associative array as options:<br>
     *              `restrictedFolder` (array) Set custom restricted folder names to array. For more description please read on `\Rdb\Modules\RdbCMSA\Libraries\SPLIterators\FilterRestricted->__construct()`. For default, it is using restricted it `FilesTrait`.<br>
     * @return bool Return `true` for successfully removed watermark, skipped because there is no backup file (never set watermark before).<br>
     *                      Return `false` for otherwise.
     */
    public function removeWatermark(string $file, array $options = []): bool
    {
        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH);

        if (is_object($this->Container) && $this->Container->has('Logger')) {
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $Logger = $this->Container->get('Logger');
        }

        // search original file (backup file).
        $originalFile = $this->searchOriginalFile($file, $options);
        if (false === $originalFile || !is_file($originalFile)) {
            // if original file (backup file) was not found.
            // this means that it was never set watermark before. skipping it.
            return true;
        }// endif check original file exists.

        // copy to main file.
        $copyResult = copy($originalFile, $file);

        if (true !== $copyResult) {
            if (isset($Logger)) {
                $Logger->write(
                    'modules/rdbcmsa/libraries/image/removewatermark',
                    3,
                    'Unable to restore original file. {file}',
                    [
                        'file' => $originalFile,
                    ]
                );
            }
            unset($copyResult, $Logger, $originalFile);
            return false;
        }

        // only successfully copied file process can goes here.
        // delete original (backup) file.
        $deleteResult = @unlink($originalFile);

        if (true !== $deleteResult) {
            if (isset($Logger)) {
                $Logger->write(
                    'modules/rdbcmsa/libraries/image/removewatermark',
                    2,
                    'Unable to delete original file. {file}',
                    [
                        'file' => $originalFile,
                    ]
                );
            }
        }
        unset($Logger, $originalFile);

        return ($copyResult === true && $deleteResult === true);
    }// removeWatermark


    /**
     * Resize thumbnails. If thumbnail exists, it will be overwrite.
     * 
     * @since 0.0.8
     * @param string $file The full path to main image file.
     */
    public function resizeThumbnails(string $file)
    {
        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH);

        // initialize the class and get instance via property.
        $this->init($file);
        $Image = $this->Image;

        // set jpg, png quality
        $Image->jpg_quality = 80;
        $Image->png_quality = 5;

        // get image size for calculation and thumbnail sizes for resize.
        $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
        $imageSize = $Image->getImageSize();
        $thumbnailSizes = $FilesSubController->getThumbnailSizes();
        unset($FilesSubController);

        $fileNameExt = $FileSystem->getFileNameExtension($file);

        // loop thumbnail sizes and resize.
        foreach ($thumbnailSizes as $sizeName => list($width, $height)) {
            if ($imageSize['width'] > $width || $imageSize['height'] > $height) {
                $saveFile = dirname($file) . DIRECTORY_SEPARATOR . $FileSystem->addSuffixFileName($fileNameExt, '_' . $sizeName);
                $Image->resize($width, $height);
                $Image->save($saveFile);
                $Image->clear();
            }
        }// endforeach;
        unset($height, $sizeName, $width);
        unset($fileNameExt, $Image, $imageSize);
    }// resizeThumbnails


    /**
     * Search for original file in the same folder with specified `$file`.
     * 
     * To use this method, the `Container` property must be set.
     * 
     * @since 0.0.8
     * @param string $file The full path to main image file. The main image file is the file that displayed on the web pages 
     *              but it is not original uploaded file that was created before apply watermark.<br>
     *              This file can be exists or not. It is just for go to the same folder and start looking for original file.<br>
     * @param array $options Associative array as options:<br>
     *              `returnFullPath` (bool) Set to `true` to return full path if found, set to `false` to return related path if found. Default is `true`.<br>
     *              `relateFrom` (string) To return related path, you must specify related from. Example: file location is /var/www/image/avatar/me.jpg `relateForm` is /var/www/image the result will be avatar/me.jpg.<br>
     *              `restrictedFolder` (array) Set custom restricted folder names to array. For more description please read on `\Rdb\Modules\RdbCMSA\Libraries\SPLIterators\FilterRestricted->__construct()`. For default, it is using restricted it `FilesTrait`.<br>
     * @return mixed Return original file if found, return `false` if not found.
     * @throws \RuntimeException If `Container` property was not set.
     */
    public function searchOriginalFile(
        string $file,
        array $options = []
    ) {
        if (!$this->Container instanceof \Rdb\System\Container) {
            throw new \RuntimeException('The property `Container` must be set.');
        }
        
        if (!isset($options['returnFullPath'])) {
            $options['returnFullPath'] = true;
        }
        if ($options['returnFullPath'] === false && !isset($options['relateFrom'])) {
            $options['relateFrom'] = dirname($file);
        }

        if (!isset($options['restrictedFolder']) || !is_array($options['restrictedFolder'])) {
            $FoldersController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\Files\FoldersController($this->Container);
            $options['restrictedFolder'] = $FoldersController->restrictedFolder;
            unset($FoldersController);
        }

        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH);
        

        $targetDir = dirname($file);
        $fileNameOnly = $FileSystem->getFileNameOnly($file);
        $fileExtOnly = $FileSystem->getFileExtensionOnly($file);

        $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
        $thumbnailSizes = $FilesSubController->getThumbnailSizes();
        unset($FilesSubController);

        // retrieve files. ---------------------------------------
        $RDI = new \RecursiveDirectoryIterator(
            $targetDir,
            \FilesystemIterator::SKIP_DOTS
        );

        // filters before \RecursiveIteratorIterator
        $RDI = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterFilenameRegex(
            $RDI,
            '/' . preg_quote($fileNameOnly) . '_original((\.[0-9]{6})*).' . $fileExtOnly . '$/'
        );

        $RII = new \RecursiveIteratorIterator(
            $RDI,
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        unset($RDI);

        // filters after \RecursiveIteratorIterator
        if (isset($options['restrictedFolder']) && is_array($options['restrictedFolder']) && !empty($options['restrictedFolder'])) {
            $RII = new SPLIterators\FilterRestricted(
                $RII, 
                $targetDir,
                $options['restrictedFolder']
            );
            $RII->notType = 'dir';
        }
        $RII = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterNoThumbnails(
            $RII,
            $FileSystem,
            $thumbnailSizes
        );
        // end retrieve files. ----------------------------------

        unset($fileExtOnly, $fileNameOnly, $FileSystem, $targetDir, $thumbnailSizes);

        foreach ($RII as $filename => $File) {
            // use $File->getFilename() for return only file name with extension.
            if ($options['returnFullPath'] === false) {
                $path = str_replace(['/', '\\', DIRECTORY_SEPARATOR], DIRECTORY_SEPARATOR, $options['relateFrom']);
                $pathname = str_replace(['/', '\\', DIRECTORY_SEPARATOR], DIRECTORY_SEPARATOR, $File->getPathname());
                $relatePath = str_replace($path . DIRECTORY_SEPARATOR, '', $pathname);
                unset($path, $pathname);

                $relatePath = str_replace(['/', '\\', DIRECTORY_SEPARATOR], '/', $relatePath);

                return $relatePath;
            } else {
                return $File->getPathname();
            }
        }// endforeach;
        unset($FI, $File, $filename);

        return false;
    }// searchOriginalFile


    /**
     * Set watermark to an image.
     * 
     * This method will be make copy of an image to append suffix `_original.nnn` before set watermark (nnn is random number).
     * 
     * To use this method, the `Container` property must be set.
     * 
     * @since 0.0.8
     * @param string $file The full path to main image file. The main image file is the file that displayed on the web pages 
     *              but it is not original uploaded file that was created before apply watermark.<br>
     * @param array $options Associative array as options:<br>
     *              `moduleBasePath` (string) The module base path where watermark file is located at. Default is empty to use this module.<br>
     *              `restrictedFolder` (array) Set custom restricted folder names to array. For more description please read on `\Rdb\Modules\RdbCMSA\Libraries\SPLIterators\FilterRestricted->__construct()`. For default, it is using restricted it `FilesTrait`.<br>
     * @return bool Return `true` for successfully set watermark, skipped because there is no watermark file uploaded in settings.<br>
     *                      Return `false` for otherwise.
     */
    public function setWatermark(string $file, array $options = []): bool
    {
        if (!isset($options['moduleBasePath']) || !is_string($options['moduleBasePath']) || empty($options['moduleBasePath'])) {
            $options['moduleBasePath'] = dirname(__DIR__);
        }

        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH);

        // get the watermark file path from config db.
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        $watermarkFile = $ConfigDb->get('rdbcmsa_watermarkfile');
        if (empty($watermarkFile)) {
            return true;
        }
        $watermarkFile = $options['moduleBasePath'] . DIRECTORY_SEPARATOR . $watermarkFile;
        unset($ConfigDb);

        if (is_object($this->Container) && $this->Container->has('Logger')) {
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $Logger = $this->Container->get('Logger');
        }

        // search for original file (backup file).
        $originalFile = $this->searchOriginalFile($file, $options);
        if (false === $originalFile || !is_file($originalFile)) {
            // if original file (backup file) was not found.
            // generate new original file for backup.
            $originalFile = $FileSystem->addSuffixFileName($file, '_original', true);
            // make backup.
            copy($file, $originalFile);

            // search original file again (backup file).
            $originalFile = $this->searchOriginalFile($file, $options);
            if (false === $originalFile || !is_file($originalFile)) {
                // if still unable to get original file (backup file).
                $originalFile = false;
                if (isset($Logger)) {
                    $Logger->write(
                        'modules/rdbcmsa/libraries/image/setwatermark', 
                        3, 
                        'Unable to copy original file. {file}', 
                        [
                            'file' => $file,
                        ]
                    );
                }// endif $Logger
                unset($FileSystem, $Logger, $originalFile, $watermarkFile);
                return false;
            }
        }// endif check original file exists.

        unset($FileSystem);

        $this->init($originalFile);
        $Image = $this->Image;
        $Image->png_quality = 5;
        unset($originalFile);

        $doWatermark = $Image->watermarkImage($watermarkFile, 'center', 'middle');
        $doResize = $Image->resize(2000, 2000);
        $doSave = $Image->save($file);
        $Image->clear();
        unset($watermarkFile);

        if (false === $doWatermark || false === $doResize) {
            if (isset($Logger)) {
                $Logger->write(
                    'modules/rdbcmsa/libraries/image/setwatermark', 
                    3, 
                    'Unable to watermark or resize the image. {file}', 
                    [
                        'file' => $originalFile,
                        'message' => $Image->status_msg,
                    ]
                );
            }// endif $Logger
        }
        unset($Image, $Logger);

        return ($doWatermark === true && $doResize === true && $doSave === true);
    }// setWatermark


}
