<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 * @deprecated since 0.0.8
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files;


/**
 * Directory name filter using regular expression.
 * 
 * @link https://stackoverflow.com/a/3322641/128761 Original source code.
 * @deprecated since 0.0.8 Use `\Rdb\Modules\RdbCMSA\Libraries\SPLIterators\FilterDirnameRegex` class instead.
 * @todo [rdbcms] Remove this class (`Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterDirnameRegex`) in version 1.0.
 * @since 0.0.6
 */
class FilterDirnameRegex extends FilterFilesystemRegex
{


    /**
     * Filter directories against the regex
     * 
     * @deprecated since 0.0.8
     */
    public function accept() {
        return (
            ! $this->isDir() || preg_match($this->regex, $this->getFilename())
        );
    }// accept


}
