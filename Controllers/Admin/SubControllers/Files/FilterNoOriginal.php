<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 * @deprecated since 0.0.8
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files;


/**
 * Filter original (backup) file or the file that contain `_original` suffix.
 * 
 * @deprecated since 0.0.8 Use `\Rdb\Modules\RdbCMSA\Libraries\SPLIterators\FilterNoOriginal` class instead.
 * @todo [rdbcms] Remove this class (`Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterNoOriginal`) in version 1.0.
 * @since 0.0.6
 */
class FilterNoOriginal extends \FilterIterator
{


    /**
     * @var \Rdb\Modules\RdbCMSA\Libraries\FileSystem
     */
    protected $FileSystem;


    /**
     * Class constructor.
     * 
     * @deprecated since 0.0.8
     * @param \Iterator $iterator The Iterator class type.
     * @param \Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem
     */
    public function __construct(\Iterator $iterator, \Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem)
    {
        trigger_error('This method has been deprecated.', E_USER_WARNING);

        parent::__construct($iterator);

        $this->FileSystem = $FileSystem;
    }// __construct


    /**
     * Check whether the current element of the iterator is acceptable
     *
     * @link https://www.php.net/manual/en/filteriterator.accept.php Original doc.
     * @deprecated since 0.0.8
     * @return bool Return `true` if it is NOT original file.
     */
    public function accept(): bool
    {
        if (!$this->isFile()) {
            return false;
        }

        $File = $this->getInnerIterator()->current();
        $filename = $File->getFilename();
        $removedSuffix = $this->FileSystem->removeSuffixFileName($filename, '_original', true);
        if ($removedSuffix !== $filename) {
            unset($File, $filename, $removedSuffix);
            return false;
        }

        // check again if it has just _original suffix.
        $removedSuffix = $this->FileSystem->removeSuffixFileName($filename, '_original');
        if ($removedSuffix !== $filename) {
            unset($File, $filename, $removedSuffix);
            return false;
        }
        return true;
    }// accept


}
