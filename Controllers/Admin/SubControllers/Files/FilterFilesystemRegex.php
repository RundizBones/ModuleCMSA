<?php


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files;


/**
 * Abstract file system regular expression filter.
 * 
 * @link https://stackoverflow.com/a/3322641/128761 Original source code.
 * @since 0.0.6
 */
abstract class FilterFilesystemRegex extends \RegexIterator
{


    protected $regex;


    /**
     * Abstract file system regular expression filter.
     * 
     * @param \Iterator $it
     * @param string $regex
     */
    public function __construct(\Iterator $it, string $regex) {
        $this->regex = $regex;
        parent::__construct($it, $regex);
    }// __construct


}
