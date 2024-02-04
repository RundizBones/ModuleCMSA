<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 * @deprecated since 0.0.8
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files;


/**
 * Abstract file system regular expression filter.
 * 
 * @link https://stackoverflow.com/a/3322641/128761 Original source code.
 * @deprecated since 0.0.8 Use `\Rdb\Modules\RdbCMSA\Libraries\SPLIterators\FilterFilesystemRegex` class instead.
 * @todo [rdbcms] Remove this class (`Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterFilesystemRegex`) in version 1.0.
 * @since 0.0.6
 */
abstract class FilterFilesystemRegex extends \RecursiveRegexIterator
{


    protected $regex;


    /**
     * Abstract file system regular expression filter.
     * 
     * These filters must be called before calling `\RecursiveIteratorIterator()` class.
     * 
     * @deprecated since 0.0.8
     * @param \Iterator $it
     * @param string $regex
     */
    public function __construct(\Iterator $it, string $regex)
    {
        trigger_error('This method has been deprecated.', E_USER_WARNING);

        $this->regex = $regex;
        parent::__construct($it, $regex);
    }// __construct


}
