<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files;


/**
 * Sub controller of files.
 * 
 * This file is like a normal class, it can be called from controllers, models while the file that extends base controller can't do this.
 * 
 * @property-read array $audioExtensions Audio extensions.
 * @property-read array $imageExtensions Image extensions.
 * @property-read array $videoExtensions Video extensions.
 */
class FilesSubController
{


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Settings\CMSAdmin\Traits\SettingsCMSATrait;


    /**
     * @link https://en.wikipedia.org/wiki/HTML5_audio Reference. Click on each container to see its extensions.
     * @link https://developer.mozilla.org/en-US/docs/Web/Media/Formats/Audio_codecs Reference. Click on each container to see its mime types.
     * @link https://www.w3schools.com/html/html5_audio.asp Reference from W3Schools.
     * @link https://filesamples.com/categories/audio Sample of audio files in each extension.
     * @var array Audio extensions. Use extension to check instead of mime type because each extension may contain different mime types.
     */
    protected $audioExtensions = ['aac', 'flac', 'ogg', 'opus', 'mp3', 'm4a', 'wav', 'webm'];


    /**
     * @var \Rdb\System\Container|null
     */
    public $Container;


    /**
     * @var array Image extensions. Useful for resize, display images.
     */
    protected $imageExtensions = ['jfif', 'jpg', 'jpeg', 'gif', 'png', 'webp'];


    /**
     * @link https://en.wikipedia.org/wiki/HTML5_video Reference. Click on each container to see its extensions.
     * @link https://developer.mozilla.org/en-US/docs/Web/Media/Formats/Video_codecs Reference. Click on each container to see its mime types.
     * @link https://www.w3schools.com/html/html5_video.asp Reference from W3Schools.
     * @link https://filesamples.com/categories/audio Sample of audio files in each extension.
     * @var array Video extensions.
     */
    protected $videoExtensions = ['m4v', 'mp4', 'ogg', 'ogv', 'webm'];


    /**
     * Magic get.
     * 
     * @param string $name
     * @return type
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
        
        return null;
    }// __get


    /**
     * Format duration.
     *
     * This method was called from `getAudioMetadata()`, `getVideoMetadata()`.
     * 
     * @link https://stackoverflow.com/a/41433276/128761 Original source code.
     * @deprecated 0.0.8 Use this method from `Rdb\Modules\RdbCMSA\Libraries\FileSystem` class instead. This will be remove on version 1.0.
     * @todo [rdbcms] Remove this method (`formatDuration`) in version 1.0.
     * @param string $duration The duration.
     * @return string Return formatted duration.
     */
    private function formatDuration(string $duration): string
    {
        trigger_error('This method has been deprecated.', E_USER_WARNING);

        // The base case is A:BB
        if(strlen($duration) == 4) {
            return "00:0" . $duration;
        }
        // If AA:BB
        else if(strlen($duration) == 5) {
            return "00:" . $duration;
        }   // If A:BB:CC
        else if(strlen($duration) == 7) {
            return "0" . $duration;
        }
    }// formatDuration


    /**
     * Get audio metadata.
     * 
     * @deprecated 0.0.8 Use this method from `Rdb\Modules\RdbCMSA\Libraries\FileSystem` class instead. This will be remove on version 1.0.
     * @todo [rdbcms] Remove this method (`getAudioMetadata`) in version 1.0.
     * @param string $file File path.
     * @return array Return array with these index.<br>
     *                      `width`<br>
     *                      `height`<br>
     *                      `frame_rate`<br>
     *                      `format`<br>
     */
    public function getAudioMetadata(string $file): array
    {
        trigger_error('This method has been deprecated.', E_USER_WARNING);

        $output = [
            'channels' => null,
            'sample_rate' => null,
            'format' => null,
        ];

        if (!is_file($file)) {
            // if file is not exists or it is folder - NOT file.
            return $output;
        }

        $GetId3 = new \getID3();
        $fileInfo = $GetId3->analyze($file);
        unset($GetId3);
        if (array_key_exists('audio', $fileInfo) && is_array($fileInfo['audio'])) {
            if (array_key_exists('channels', $fileInfo['audio'])) {
                $output['channels'] = $fileInfo['audio']['channels'];
            }
            if (array_key_exists('sample_rate', $fileInfo['audio'])) {
                $output['sample_rate'] = $fileInfo['audio']['sample_rate'];
            }
            if (array_key_exists('dataformat', $fileInfo['audio'])) {
                $output['format'] = $fileInfo['audio']['dataformat'];
            }
            if (array_key_exists('playtime_string', $fileInfo)) {
                $output['duration'] = $this->formatDuration($fileInfo['playtime_string']);
            }
        }
        unset($fileInfo);

        return $output;
    }// getAudioMetadata


    /**
     * Get image metadata (such as width, height).
     * 
     * @deprecated 0.0.8 Use this method from `Rdb\Modules\RdbCMSA\Libraries\FileSystem` class instead. This will be remove on version 1.0.
     * @todo [rdbcms] Remove this method (`getImageMetadata`) in version 1.0.
     * @param string $file File path.
     * @return array Return array with `width` and `height` indexes.
     */
    public function getImageMetadata(string $file): array
    {
        trigger_error('This method has been deprecated.', E_USER_WARNING);

        $output = [
            'width' => null,
            'height' => null,
        ];

        if (!is_file($file)) {
            // if file is not exists or it is folder - NOT file.
            return $output;
        }

        $imageData = @getimagesize($file);
        if (
            is_array($imageData) &&
            array_key_exists(0, $imageData) &&
            array_key_exists(1, $imageData) &&
            is_numeric($imageData[0]) &&
            is_numeric($imageData[1])
        ) {
            $output['width'] = $imageData[0];
            $output['height'] = $imageData[1];
        }

        return $output;
    }// getImageMetadata


    /**
     * Get thumbnail sizes.
     * 
     * @return array Return array of thumbnail sizes. (Ascending sizes.) Array structure will be:<pre>
     * array(
     *     'thumb100' => array(100, 100),// first array value is width, second is height.
     *     'thumb300' => array(300, 300),
     *     'thumb600' => array(600, 600),
     *     'thumb900' => array(900, 900),
     * )
     * </pre>
     */
    public function getThumbnailSizes(): array
    {
        return [
            'thumb100' => [100, 100],
            'thumb300' => [300, 300],
            'thumb600' => [600, 600],
            'thumb900' => [900, 900],
        ];
    }// getThumbnailSizes


    /**
     * Get video metadata (such as width, height, etc).
     * 
     * @deprecated 0.0.8 Use this method from `Rdb\Modules\RdbCMSA\Libraries\FileSystem` class instead. This will be remove on version 1.0.
     * @todo [rdbcms] Remove this method (`getVideoMetadata`) in version 1.0.
     * @param string $file File path.
     * @return array Return array with these index.<br>
     *                      `width`<br>
     *                      `height`<br>
     *                      `frame_rate`<br>
     *                      `format`<br>
     */
    public function getVideoMetadata(string $file): array
    {
        trigger_error('This method has been deprecated.', E_USER_WARNING);

        $output = [
            'width' => null,
            'height' => null,
            'frame_rate' => null,
            'format' => null,
        ];

        if (!is_file($file)) {
            // if file is not exists or it is folder - NOT file.
            return $output;
        }

        $GetId3 = new \getID3();
        $fileInfo = $GetId3->analyze($file);
        unset($GetId3);
        if (array_key_exists('video', $fileInfo) && is_array($fileInfo['video'])) {
            if (array_key_exists('resolution_y', $fileInfo['video'])) {
                $output['height'] = $fileInfo['video']['resolution_y'];
            }
            if (array_key_exists('resolution_x', $fileInfo['video'])) {
                $output['width'] = $fileInfo['video']['resolution_x'];
            }
            if (array_key_exists('frame_rate', $fileInfo['video'])) {
                $output['frame_rate'] = $fileInfo['video']['frame_rate'];
            }
            if (array_key_exists('dataformat', $fileInfo['video'])) {
                $output['format'] = $fileInfo['video']['dataformat'];
            }
            if (array_key_exists('playtime_string', $fileInfo)) {
                $output['duration'] = $this->formatDuration($fileInfo['playtime_string']);
            }
        }
        unset($fileInfo);

        return $output;
    }// getVideoMetadata


    /**
     * Check if folder specified is in restricted folder. Case insensitive.
     * 
     * @param string $folderToAct The folder to check. Related from [public]/[root public folder].
     * @param array $restrictedFolders The restricted folders. See `\Rdb\Modules\RdbCMSA\Controllers\Admin\Files\FoldersController::restrictedFolder` property.
     * @return bool Return `true` if restricted, `false` for not.
     */
    public function isRestrictedFolder(string $folderToAct, array $restrictedFolders): bool
    {
        $output = false;

        foreach ($restrictedFolders as $restrictedFolder) {
            if (stripos($folderToAct, $restrictedFolder) === 0) {
                $output = true;
                break;
            }
        }// endforeach;
        unset($restrictedFolder);

        return $output;
    }// isRestrictedFolder


    /**
     * Remove watermark from uploaded image. This method will be make copy of backup original uploaded image to main file.
     * 
     * To use this method, the `Container` property must be set.
     * 
     * @deprecated 0.0.8 Use this method from `\Rdb\Modules\RdbCMSA\Libraries\Image` class instead. This will be remove on version 1.0.
     * @todo [rdbcms] Remove this method (`removeWatermark`) in version 1.0.
     * @param array $item The associative array must contain keys:<br>
     *                      `full_path_new_name` (string) The full path to main image file.<br>
     *                      `new_name` (string) The file name with extension that was renamed. No slash or path or directory included.<br>
     * @param \Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem The file system class.
     * @throws \InvalidArgumentException Throw the errors if the required array key is not exists.
     * @return bool Return `true` for successfully removed watermark, skipped because there is no backup file (never set watermark before).<br>
     *                      Return `false` for otherwise.
     */
    public function removeWatermark(array $item, ?\Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem = null)
    {
        trigger_error('This method has been deprecated.', E_USER_WARNING);

        if (!array_key_exists('full_path_new_name', $item)) {
            throw new \InvalidArgumentException('The array key `full_path_new_name` for full path to main image file is required.');
        }
        if (!array_key_exists('new_name', $item)) {
            throw new \InvalidArgumentException('The array key `new_name` for file name with extension is required.');
        }

        if (is_null($FileSystem)) {
            $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH);
        }

        if (is_object($this->Container) && $this->Container->has('Logger')) {
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $Logger = $this->Container->get('Logger');
        }

        // search original file (backup file).
        $Image = new \Rdb\Modules\RdbCMSA\Libraries\Image('');
        $Image->Container = $this->Container;
        $originalFile = $Image->searchOriginalFile($item['full_path_new_name']);
        if (false === $originalFile || !is_file($originalFile)) {
            // if original file (backup file) was not found.
            // this means that it was never set watermark before.
            return true;
        }// endif check original file exists.
        unset($Image);

        // copy to main file.
        $copyResult = copy($originalFile, $item['full_path_new_name']);

        if (true !== $copyResult) {
            if (isset($Logger)) {
                $Logger->write(
                    'modules/rdbcmsa/controllers/admin/subcontrollers/files/removewatermark',
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

        // delete original (backup) file.
        $deleteResult = @unlink($originalFile);

        return ($copyResult === true && $deleteResult === true);
    }// removeWatermark


    /**
     * Resize thumbnails. If thumbnail exists, it will be overwrite.
     * 
     * @deprecated 0.0.8 Use this method from `\Rdb\Modules\RdbCMSA\Libraries\Image` class instead. This will be remove on version 1.0.
     * @todo [rdbcms] Remove this method (`resizeThumbnails`) in version 1.0.
     * @param array $item The associative array must contain keys:<br>
     *                      `full_path_new_name` (string) The full path to main image file.<br>
     *                      `new_name` (string) The file name with extension that was renamed. No slash or path or directory included.<br>
     * @param \Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem The file system class.
     * @throws \InvalidArgumentException Throw the errors if the required array key is not exists.
     */
    public function resizeThumbnails(array $item, ?\Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem = null)
    {
        trigger_error('This method has been deprecated.', E_USER_WARNING);

        if (!array_key_exists('full_path_new_name', $item)) {
            throw new \InvalidArgumentException('The array key `full_path_new_name` for full path to main image file is required.');
        }
        if (!array_key_exists('new_name', $item)) {
            throw new \InvalidArgumentException('The array key `new_name` for file name with extension is required.');
        }

        if (is_null($FileSystem)) {
            $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH);
        }

        // initialize the class and get instance via property.
        $RdbCMSAImage = new \Rdb\Modules\RdbCMSA\Libraries\Image($item['full_path_new_name']);
        $Image = $RdbCMSAImage->Image;
        unset($RdbCMSAImage);

        // set jpg, png quality
        $Image->jpg_quality = 80;
        $Image->png_quality = 5;

        // get image size for calculation and thumbnail sizes for resize.
        $imageSize = $Image->getImageSize();
        $thumbnailSizes = $this->getThumbnailSizes();

        // loop thumbnail sizes and resize.
        foreach ($thumbnailSizes as $name => list($width, $height)) {
            if ($imageSize['width'] > $width || $imageSize['height'] > $height) {
                $saveFile = dirname($item['full_path_new_name']) . DIRECTORY_SEPARATOR . $FileSystem->addSuffixFileName($item['new_name'], '_' . $name);
                $Image->resize($width, $height);
                $Image->save($saveFile);
                $Image->clear();
            }
        }// endforeach;
        unset($height, $name, $width);
        unset($Image, $imageSize);
    }// resizeThumbnails


    /**
     * Search for original file.
     * 
     * @deprecated 0.0.8 Use this method from `\Rdb\Modules\RdbCMSA\Libraries\Image` class instead. This will be remove on version 1.0.
     * @todo [rdbcms] Remove this method (`searchOriginalFile`) in version 1.0.
     * @param array $item The associative array must contain keys:<br>
     *                      `full_path_new_name` (string) The full path to main image file.<br>
     * @param \Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem The file system class.
     * @param array $options Associative array as options:<br>
     *              `returnFullPath` (bool) Set to `true` to return full path if found, set to `false` to return related path if found. Default is `true`.<br>
     *              `relateFrom` (string) To return related path, you must specify related from. Example: file location is /var/www/image/avatar/me.jpg `relateForm` is /var/www/image the result will be avatar/me.jpg.<br>
     * @return mixed Return original file if found, return `false` if not found.
     */
    public function searchOriginalFile(
        array $item, 
        ?\Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem = null,
        array $options = []
    ) {
        trigger_error('This method has been deprecated.', E_USER_WARNING);

        if (!isset($options['returnFullPath'])) {
            $options['returnFullPath'] = true;
        }
        if ($options['returnFullPath'] === false && !isset($options['relateFrom'])) {
            $options['relateFrom'] = dirname($item['full_path_new_name']);
        }

        if (is_null($FileSystem)) {
            $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH);
        }
        $FoldersController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\Files\FoldersController($this->Container);

        $targetDir = dirname($item['full_path_new_name']);
        $fileNameOnly = $FileSystem->getFileNameOnly($item['full_path_new_name']);
        $fileExtOnly = $FileSystem->getFileExtensionOnly($item['full_path_new_name']);

        $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
        $thumbnailSizes = $FilesSubController->getThumbnailSizes();
        unset($FilesSubController);

        $RDI = new \RecursiveDirectoryIterator(
            $targetDir,
            \FilesystemIterator::SKIP_DOTS
        );
        // filters before \RecursiveIteratorIterator
        $RDI = new \Rdb\Modules\RdbCMSA\Libraries\SPLIterators\FilterFilenameRegex(
            $RDI,
            '/' . preg_quote($fileNameOnly, '/') . '_original((\.[0-9]{6})*).' . $fileExtOnly . '$/'
        );
        $RII = new \RecursiveIteratorIterator(
            $RDI,
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        unset($RDI);

        // filters after \RecursiveIteratorIterator
        $FI = new \Rdb\Modules\RdbCMSA\Libraries\SPLIterators\FilterRestricted(
            $RII, 
            $targetDir,
            $FoldersController->restrictedFolder
        );
        $FI->notType = 'dir';
        $FI = new \Rdb\Modules\RdbCMSA\Libraries\SPLIterators\FilterNoThumbnails(
            $FI,
            $FileSystem,
            $thumbnailSizes
        );
        unset($fileExtOnly, $fileNameOnly, $FileSystem, $FoldersController, $targetDir, $thumbnailSizes);

        foreach ($FI as $filename => $File) {
            // use $File->getFilename() for return only file name with extension.
            if ($options['returnFullPath'] === false) {
                $path = str_replace(['/', '\\', DIRECTORY_SEPARATOR], DIRECTORY_SEPARATOR, $options['relateFrom']);
                $relatePath = str_replace($path . DIRECTORY_SEPARATOR, '', $File->getPathname());
                unset($path);
                $relatePath = str_replace(['/', '\\', DIRECTORY_SEPARATOR], '/', $relatePath);

                return $relatePath;
            } else {
                return $File->getPathname();
            }
        }// endforeach;
        unset($File, $filename);

        return false;
    }// searchOriginalFile


    /**
     * Set watermark to uploaded image. This method will be make copy of uploaded image to append suffix `_original` before set watermark.
     * 
     * To use this method, the `Container` property must be set.
     * 
     * @deprecated 0.0.8 Use this method from `\Rdb\Modules\RdbCMSA\Libraries\Image` class instead. This will be remove on version 1.0.
     * @todo [rdbcms] Remove this method (`setWatermark`) in version 1.0.
     * @param array $item The associative array must contain keys:<br>
     *                      `full_path_new_name` (string) The full path to main image file.<br>
     *                      `new_name` (string) The file name with extension that was renamed. No slash or path or directory included.<br>
     * @param \Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem The file system class.
     * @throws \InvalidArgumentException Throw the errors if the required array key is not exists.
     * @return bool Return `true` for successfully set watermark, skipped because there is no watermark file uploaded in settings.<br>
     *                      Return `false` for otherwise.
     */
    public function setWatermark(array $item, ?\Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem = null): bool
    {
        trigger_error('This method has been deprecated.', E_USER_WARNING);

        if (!array_key_exists('full_path_new_name', $item)) {
            throw new \InvalidArgumentException('The array key `full_path_new_name` for full path to main image file is required.');
        }
        if (!array_key_exists('new_name', $item)) {
            throw new \InvalidArgumentException('The array key `new_name` for file name with extension is required.');
        }

        if (is_null($FileSystem)) {
            $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH);
        }

        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        $watermarkFile = $ConfigDb->get('rdbcmsa_watermarkfile');
        if (empty($watermarkFile)) {
            return true;
        }
        $watermarkFile = $this->getWatermarkModuleBasePath() . DIRECTORY_SEPARATOR . $watermarkFile;
        unset($ConfigDb);

        if (is_object($this->Container) && $this->Container->has('Logger')) {
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $Logger = $this->Container->get('Logger');
        }

        // search for original file (backup file).
        $Image = new \Rdb\Modules\RdbCMSA\Libraries\Image('');
        $Image->Container = $this->Container;
        $originalFile = $Image->searchOriginalFile($item['full_path_new_name']);
        if (false === $originalFile || !is_file($originalFile)) {
            // if original file (backup file) was not found.
            // generate new original file for backup.
            $originalFile = $FileSystem->addSuffixFileName($item['full_path_new_name'], '_original', true);
            // make backup.
            copy($item['full_path_new_name'], $originalFile);
            // search original file again (backup file).
            $originalFile = $Image->searchOriginalFile($item['full_path_new_name']);
            if (false === $originalFile || !is_file($originalFile)) {
                // if still unable to get original file (backup file).
                $originalFile = false;
                if (isset($Logger)) {
                    $Logger->write(
                        'modules/rdbcmsa/controllers/admin/subcontrollers/files/setwatermark', 
                        3, 
                        'Unable to copy original file. {file}', 
                        [
                            'file' => $item['full_path_new_name'],
                        ]
                    );
                }// endif $Logger
                unset($Logger, $watermarkFile);
                return false;
            }
        }// endif check original file exists.
        unset($Image);

        $RdbCMSAImage = new \Rdb\Modules\RdbCMSA\Libraries\Image($originalFile);
        $Image = $RdbCMSAImage->Image;
        unset($RdbCMSAImage);
        $Image->png_quality = 5;

        $doWatermark = $Image->watermarkImage($watermarkFile, 'center', 'middle');
        $doResize = $Image->resize(2000, 2000);
        $doSave = $Image->save($item['full_path_new_name']);
        $Image->clear();
        unset($Image, $watermarkFile);

        return ($doWatermark === true && $doResize === true && $doSave === true);
    }// setWatermark


}// FilesSubController
