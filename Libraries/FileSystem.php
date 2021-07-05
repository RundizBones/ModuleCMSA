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
     * @var int Total number of digits that will be use in add and remove suffix.
     */
    protected $randomSuffixNumberDigits = 6;


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
            $randomSuffix = '.' . mt_rand(pow(10, $this->randomSuffixNumberDigits-1), pow(10, $this->randomSuffixNumberDigits)-1);
        } else {
            $randomSuffix = '';
        }

        return $fileNameNoExt . $suffix . $randomSuffix . '.' . $fileExt;
    }// addSuffixFileName


    /**
     * Format duration.
     *
     * This method was called from `getAudioMetadata()`, `getVideoMetadata()`.
     * 
     * @link https://stackoverflow.com/a/41433276/128761 Original source code.
     * @since 0.0.8
     * @param string $duration The duration.
     * @return string Return formatted duration.
     */
    private function formatDuration(string $duration): string
    {

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
     * @since 0.0.8
     * @param string $file File path. It can be full path or related path from root in constructor. To use full path, the option must be set.
     * @param array $options Associative array keys:<br>
     *                  `fullPath` (bool) Set to `false` to connect path with root in constructor, set to `true` to use full path. Default is `false`.<br>
     * @return array Return array with these index.<br>
     *                      `width`<br>
     *                      `height`<br>
     *                      `frame_rate`<br>
     *                      `format`<br>
     */
    public function getAudioMetadata(string $file, array $options = []): array
    {
        $output = [
            'channels' => null,
            'sample_rate' => null,
            'format' => null,
        ];

        if (!isset($options['fullPath']) || $options['fullPath'] === false) {
            $file = $this->root . DIRECTORY_SEPARATOR . $this->removeUpperPath($file);
        }

        if (!is_file($file) || !class_exists('\\getID3')) {
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
     * @since 0.0.8
     * @param string $file File path. It can be full path or related path from root in constructor. To use full path, the option must be set.
     * @param array $options Associative array keys:<br>
     *                  `fullPath` (bool) Set to `false` to connect path with root in constructor, set to `true` to use full path. Default is `false`.<br>
     * @return array Return array with `width` and `height` indexes.
     */
    public function getImageMetadata(string $file, array $options = []): array
    {
        $output = [
            'width' => null,
            'height' => null,
        ];

        if (!isset($options['fullPath']) || $options['fullPath'] === false) {
            $file = $this->root . DIRECTORY_SEPARATOR . $this->removeUpperPath($file);
        }

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
     * Get video metadata (such as width, height, etc).
     * 
     * @since 0.0.8
     * @param string $file File path. It can be full path or related path from root in constructor. To use full path, the option must be set.
     * @param array $options Associative array keys:<br>
     *                  `fullPath` (bool) Set to `false` to connect path with root in constructor, set to `true` to use full path. Default is `false`.<br>
     * @return array Return array with these index.<br>
     *                      `width`<br>
     *                      `height`<br>
     *                      `frame_rate`<br>
     *                      `format`<br>
     */
    public function getVideoMetadata(string $file, array $options = []): array
    {
        $output = [
            'width' => null,
            'height' => null,
            'frame_rate' => null,
            'format' => null,
        ];

        if (!isset($options['fullPath']) || $options['fullPath'] === false) {
            $file = $this->root . DIRECTORY_SEPARATOR . $this->removeUpperPath($file);
        }

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
            $randomSuffix = '(\.[0-9]{' . $this->randomSuffixNumberDigits . '})';
            unset($digits);
        } else {
            $randomSuffix = '';
        }

        $replaced = preg_replace('/' . preg_quote($suffix, '/') . $randomSuffix . '$/', '', $fileNameNoExt);
        unset($fileNameNoExt, $randomSuffix);

        return $replaced . '.' . $fileExt;
    }// removeSuffixFileName


}
