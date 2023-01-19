<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Libraries\SPLIterators;


/**
 * Path name filter using regular expression.
 * 
 * Files and directories full path name must match regular expression.
 * 
 * @link https://stackoverflow.com/a/3322641/128761 Original source code.
 * @since 0.0.8 Moved from `\Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterDirnameRegex`.
 */
class FilterPathnameRegex extends FilterFilesystemRegex
{


    /**
     * Filter directories against the regex
     */
    public function accept(): bool {
        return (
            preg_match($this->regex, $this->getPathname())
        );
    }// accept


}
