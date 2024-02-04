<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 * @deprecated since 0.0.8
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files;


/**
 * File name filter using regular expression.
 * 
 * @link https://stackoverflow.com/a/3322641/128761 Original source code.
 * @deprecated since 0.0.8 Use `\Rdb\Modules\RdbCMSA\Libraries\SPLIterators\FilterFilenameRegex` class instead.
 * @todo [rdbcms] Remove this class (`Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterFilenameRegex`) in version 1.0.
 * @since 0.0.6
 */
class FilterFilenameRegex extends FilterFilesystemRegex
{


    public function __construct(\Iterator $it, string $regex)
    {
        trigger_error('This method has been deprecated.', E_USER_WARNING);

        parent::__construct($it, $regex);
    }


    /**
     * Filter files against the regex
     * 
     * @deprecated since 0.0.8
     */
    public function accept() {
        return (
            ! $this->isFile() || preg_match($this->regex, $this->getFilename())
        );
    }// accept


}
