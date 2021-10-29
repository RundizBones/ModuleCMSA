<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Libraries\SPLIterators;


/**
 * File name filter using regular expression.
 * 
 * NOT file = reject,
 * IS file = must match regular expression pattern.
 * 
 * @link https://stackoverflow.com/a/3322641/128761 Original source code.
 * @since 0.0.8 Moved from `\Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterFilenameRegex`.
 */
class FilterFilenameRegex extends FilterFilesystemRegex
{


    /**
     * Filter files against the regex
     */
    public function accept() {
        return (
            ! $this->isFile() || preg_match($this->regex, $this->getFilename())
        );
    }// accept


}
