<?php


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files;


/**
 * File name filter using regular expression.
 * 
 * @link https://stackoverflow.com/a/3322641/128761 Original source code.
 * @since 0.0.6
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
