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
 */
class Image
{


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
    public function __construct($source_image_path)
    {
        if (extension_loaded('imagick') === true) {
            $this->Image = new \Rundiz\Image\Drivers\Imagick($source_image_path);
            $this->driverUse = 'imagick';
        } else {
            $this->Image = new \Rundiz\Image\Drivers\Gd($source_image_path);
            $this->driverUse = 'gd';
        }

        return $this->Image;
    }// __construct


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


}
