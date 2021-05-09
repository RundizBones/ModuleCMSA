<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files;


/**
 * Filter original (backup) file or the file that contain `_original` suffix.
 * 
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
     * @param \Iterator $iterator The Iterator class type.
     * @param \Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem
     */
    public function __construct(\Iterator $iterator, \Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem)
    {
        parent::__construct($iterator);

        $this->FileSystem = $FileSystem;
    }// __construct


    /**
     * Check whether the current element of the iterator is acceptable
     *
     * @link https://www.php.net/manual/en/filteriterator.accept.php Original doc.
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
