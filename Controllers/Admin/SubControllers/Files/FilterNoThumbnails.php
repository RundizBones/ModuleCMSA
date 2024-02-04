<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 * @deprecated since 0.0.8
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files;


/**
 * Filter thumbnails or the files that contain` _thumbxxx` size suffix in `thumbnailSizes` property.
 * 
 * @deprecated since 0.0.8 Use `\Rdb\Modules\RdbCMSA\Libraries\SPLIterators\FilterNoThumbnails` class instead.
 * @todo [rdbcms] Remove this class (`Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterNoThumbnails`) in version 1.0.
 * @since 0.0.1
 */
class FilterNoThumbnails extends \FilterIterator
{


    /**
     * @var \Rdb\Modules\RdbCMSA\Libraries\FileSystem
     */
    protected $FileSystem;


    /**
     * @var array
     */
    protected $thumbnailSizes;


    /**
     * Class constructor.
     * 
     * @deprecated since 0.0.8
     * @param \Iterator $iterator The Iterator class type.
     * @param \Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem
     * @param array $thumbnailSizes The thumbnail sizes that have got from `\Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController->getThumbnailSizes()`.
     */
    public function __construct(\Iterator $iterator, \Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem, array $thumbnailSizes)
    {
        trigger_error('This method has been deprecated.', E_USER_WARNING);

        parent::__construct($iterator);

        $this->FileSystem = $FileSystem;
        $this->thumbnailSizes = $thumbnailSizes;
    }// __construct


    /**
     * Check whether the current element of the iterator is acceptable
     *
     * @link https://www.php.net/manual/en/filteriterator.accept.php Original doc.
     * @deprecated since 0.0.8
     * @return bool Return `true` if it is not thumbnail file.
     */
    public function accept(): bool
    {
        $File = $this->getInnerIterator()->current();
        $filename = $File->getFilename();

        foreach ($this->thumbnailSizes as $sizeName => $dimensions) {
            $removedSuffix = $this->FileSystem->removeSuffixFileName($filename, '_' . $sizeName);
            if ($removedSuffix !== $filename) {
                unset($filename, $removedSuffix);
                return false;
            }
            unset($removedSuffix);
        }// endforeach;
        unset($dimensions, $filename, $sizeName);

        return true;
    }// accept


}
