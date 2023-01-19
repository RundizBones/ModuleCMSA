<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Libraries\SPLIterators;


/**
 * Directory name filter using regular expression.
 * 
 * NOT directory = reject,
 * IS directory = must match regular expression pattern.
 * 
 * @link https://stackoverflow.com/a/3322641/128761 Original source code.
 * @since 0.0.8 Moved from `\Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterDirnameRegex`.
 */
class FilterDirnameRegex extends FilterFilesystemRegex
{


    /**
     * Filter directories against the regex
     */
    public function accept(): bool {
        return (
            ! $this->isDir() || preg_match($this->regex, $this->getFilename())
        );
    }// accept


}
