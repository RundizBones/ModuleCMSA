<?php


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files;


/**
 * Directory name filter using regular expression.
 * 
 * @link https://stackoverflow.com/a/3322641/128761 Original source code.
 * @since 0.0.6
 */
class FilterDirnameRegex extends FilterFilesystemRegex
{


    /**
     * Filter directories against the regex
     */
    public function accept() {
        return (
            ! $this->isDir() || preg_match($this->regex, $this->getFilename())
        );
    }// accept


}
