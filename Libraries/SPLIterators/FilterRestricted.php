<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Libraries\SPLIterators;


/**
 * Filter restricted folders and files.
 * 
 * @since 0.0.8 Moved from `\Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterRestricted`.
 */
class FilterRestricted extends \FilterIterator
{


    /**
     * @var string Filter to only type. Accept: `dir`, `file`, `link`.<br>
     *                  You can use `|` for `or` conditions.
     */
    public $notType;


    protected $targetDir;


    protected $userFilter;


    /**
     * Filtered restricted folders and files.
     * 
     * Example: `new FilterRestricted($It, '/var/www', ['abc'])` Will be filter out only `/var/www/abc` and all sub directories.<br>
     * Anything like these will NOT filtered out: `/var/www/home/abc`, `/var/www/page1/abc`.
     * 
     * @param \Iterator $iterator The Iterator class type.
     * @param string $targetDir Target base folder without trailing slash. This will be use to remove from real path and left only related path.
     * @param array $filter The restricted files or folders.
     */
    public function __construct(\Iterator $iterator, string $targetDir, array $filter)
    {
        parent::__construct($iterator);

        $this->targetDir = $targetDir;
        $this->userFilter = $filter;
    }// __construct


    /**
     * Check whether the current element of the iterator is acceptable
     *
     * @link https://www.php.net/manual/en/filteriterator.accept.php Original doc.
     * @return bool
     */
    public function accept(): bool
    {
        $File = $this->getInnerIterator()->current();

        $splitNotTypes = explode('|', $this->notType);
        foreach ($splitNotTypes as $notType) {
            if ($notType === 'dir') {
                if ($File->isDir()) {
                    return false;
                }
            } elseif ($notType === 'file') {
                if ($File->isFile()) {
                    return false;
                }
            } elseif ($notType === 'link') {
                if ($File->isLink()) {
                    return false;
                }
            }
        }// endforeach;
        unset($notType, $splitNotTypes);

        $removedPathToTarget = str_replace(
            $this->targetDir . DIRECTORY_SEPARATOR, 
            '', 
            $File->getPathname()
        );

        $relatePath = str_replace(
            ['/', '\\', DIRECTORY_SEPARATOR],
            '/',
            $removedPathToTarget
        );

        if ($this->isRestrictedFolder($relatePath, $this->userFilter)) {
            return false;
        }

        unset($relatePath);
        return true;
    }// accept


    /**
     * Check if folder specified is in restricted folder. Case insensitive.
     * 
     * @param string $folderToAct The folder to check. Related from target.
     * @param array $restrictedFolders The restricted folders.
     * @return bool Return `true` if restricted, `false` for not.
     */
    protected function isRestrictedFolder(string $folderToAct, array $restrictedFolders): bool
    {
        $output = false;

        foreach ($restrictedFolders as $restrictedFolder) {
            $restrictedFolder = str_replace(
                ['/', '\\', DIRECTORY_SEPARATOR], 
                '/', 
                $restrictedFolder
            );

            if (stripos($folderToAct, $restrictedFolder) === 0) {
                // if, example: a/b in a/b/c/d.txt = YES restricted.
                // if, example: a/b in a/aaa/a/b/e.txt = NO, not restricted.
                // if, example: a/b in z/x/a/b = NO, not restricted.
                // to make case sensitive, use `strpos()` instead but filters must write more.
                $output = true;
                break;
            }
        }// endforeach;
        unset($restrictedFolder);

        return $output;
    }// isRestrictedFolder


}
