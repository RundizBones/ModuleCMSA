<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Libraries\SPLIterators;


/**
 * Abstract file system regular expression filter.
 * 
 * @link https://stackoverflow.com/a/3322641/128761 Original source code.
 * @since 0.0.8 Moved from `\Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterFilesystemRegex`.
 */
abstract class FilterFilesystemRegex extends \RecursiveRegexIterator
{


    protected $regex;


    /**
     * Abstract file system regular expression filter.
     * 
     * These filters must be called before calling `\RecursiveIteratorIterator()` class.
     * 
     * @param \Iterator $it
     * @param string $regex
     */
    public function __construct(\Iterator $it, string $regex) {
        $this->regex = $regex;
        parent::__construct($it, $regex);
    }// __construct


}
