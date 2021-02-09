<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers;


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


    /**
     * @link https://en.wikipedia.org/wiki/HTML5_audio Reference. Click on each container to see its extensions.
     * @link https://developer.mozilla.org/en-US/docs/Web/Media/Formats/Audio_codecs Reference. Click on each container to see its mime types.
     * @link https://www.w3schools.com/html/html5_audio.asp Reference from W3Schools.
     * @link https://filesamples.com/categories/audio Sample of audio files in each extension.
     * @var array Audio extensions. Use extension to check instead of mime type because each extension may contain different mime types.
     */
    protected $audioExtensions = ['aac', 'flac', 'ogg', 'opus', 'mp3', 'm4a', 'wav', 'webm'];


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
     * @link https://stackoverflow.com/a/41433276/128761 Original source code.
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
     * @param string $file File path.
     * @return array Return array with these index.<br>
     *                      `width`<br>
     *                      `height`<br>
     *                      `frame_rate`<br>
     *                      `format`<br>
     */
    public function getAudioMetadata(string $file): array
    {
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
     * @param string $file File path.
     * @return array Return array with `width` and `height` indexes.
     */
    public function getImageMetadata(string $file): array
    {
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
     * )
     * </pre>
     */
    public function getThumbnailSizes(): array
    {
        return [
            'thumb100' => [100, 100],
            'thumb300' => [300, 300],
            'thumb600' => [600, 600],
        ];
    }// getThumbnailSizes


    /**
     * Get video metadata (such as width, height, etc).
     * 
     * @param string $file File path.
     * @return array Return array with these index.<br>
     *                      `width`<br>
     *                      `height`<br>
     *                      `frame_rate`<br>
     *                      `format`<br>
     */
    public function getVideoMetadata(string $file): array
    {
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


}// FilesSubController
