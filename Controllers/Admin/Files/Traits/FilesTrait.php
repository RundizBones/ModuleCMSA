<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Files\Traits;


/**
 * Files trait.
 * 
 * @since 0.0.1
 */
trait FilesTrait
{


    /**
     * @var array Restricted folder name to list, delete, rename. Case insensitive.<br>
     *                  Start by related from [public folder]/[rootPublicFolderName or default is rdbadmin-public]/.<br>
     *                  Example: ['avatar'] means [public]/[root public folder]/avatar and everything in it will be restricted.
     */
    protected $restrictedFolder = ['avatar', 'favicon'];


    /**
     * @var string Root public storage folder name for upload files.
     */
    protected $rootPublicFolderName = 'rdbadmin-public';


    /**
     * Get URLs and methods for management controller.
     * 
     * @return array
     */
    protected function getFilesUrlsMethod()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);

        $output = [];

        $output['addFileRESTUrl'] = $urlAppBased . '/admin/cms/files';
        $output['addFileRESTMethod'] = 'POST';

        $output['editFileUrlBase'] = $urlAppBased . '/admin/cms/files/edit';
        $output['editFileRESTUrlBase'] = $urlAppBased . '/admin/cms/files';
        $output['editFileRESTMethod'] = 'PATCH';

        $output['getFilesUrl'] = $urlAppBased . '/admin/cms/files';
        $output['getFilesRESTUrl'] = $urlAppBased . '/admin/cms/files';
        $output['getFilesRESTMethod'] = 'GET';

        $output['getFileBrowserUrl'] = $urlAppBased . '/admin/cms/files/browser';

        $output['getFileRESTUrlBase'] = $urlAppBased . '/admin/cms/files';
        $output['getFileRESTMethod'] = 'GET';
        $output['downloadFileUrl'] = $urlAppBased . '/admin/cms/files/%file_id%/downloads';

        $output['actionsFilesUrl'] = $urlAppBased . '/admin/cms/files/actions';
        $output['moveFileRESTUrl'] = $urlAppBased . '/admin/cms/files/%file_ids%/move';
        $output['moveFileRESTMethod'] = 'POST';
        $output['deleteFileRESTUrlBase'] = $urlAppBased . '/admin/cms/files';
        $output['deleteFileRESTMethod'] = 'DELETE';
        $output['updateFileDataRESTUrl'] = $urlAppBased . '/admin/cms/files/%file_ids%/%action%';
        $output['updateFileDataRESTMethod'] = 'PATCH';

        $output['scanUnindexedUrl'] = $urlAppBased . '/admin/cms/files/scan-unindexed';
        $output['scanUnindexedRestUrl'] = $output['scanUnindexedUrl'];
        $output['scanUnindexedRestMethod'] = 'GET';
        $output['scanUnindexedAddRestUrl'] = $output['scanUnindexedUrl'];
        $output['scanUnindexedAddRestMethod'] = 'POST';

        // folder tasks. -------------------
        $output['getFoldersRESTUrl'] = $urlAppBased . '/admin/cms/files/folders';
        $output['getFoldersRESTMethod'] = 'GET';

        $output['newFolderRESTUrl'] = $urlAppBased . '/admin/cms/files/folders';
        $output['newFolderRESTMethod'] = 'POST';

        $output['renameFolderRESTUrl'] = $urlAppBased . '/admin/cms/files/folders';
        $output['renameFolderRESTMethod'] = 'PATCH';

        $output['deleteFolderRESTUrl'] = $urlAppBased . '/admin/cms/files/folders';
        $output['deleteFolderRESTMethod'] = 'DELETE';
        // end folder tasks. --------------

        $output['viewFileFrontUrl'] = $urlAppBased . '/files/%file_id%';

        unset($Url, $urlAppBased);

        return $output;
    }// getFilesUrlsMethod


}
