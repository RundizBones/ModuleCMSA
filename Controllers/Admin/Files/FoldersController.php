<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Files;


/**
 * Folders management controller.
 * 
 * @since 0.0.1
 * @property-read array $restrictedFolder See `Rdb\Modules\RdbCMSA\Controllers\Admin\Files\Traits\FilesTrait->restrictedFolder` property for more details.
 */
class FoldersController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\FilesTrait;


    /**
     * Magic get.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
        return ;
    }// __get


    /**
     * Delete selected folder.
     * 
     * @return string
     */
    public function doDeleteFolderAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['list', 'add', 'edit', 'delete', 'move']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf([
            'persistentTokenMode' => true,
        ]);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getFilesUrlsMethod();

        // make patch data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if validate csrf passed.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            // prepare data
            $data = [];
            $data['folderrelpath'] = str_replace(['\\', '/', DIRECTORY_SEPARATOR], '/', trim($this->Input->delete('folderrelpath')));
            $data['folderrelpath'] = trim($data['folderrelpath'], '/');

            $FileSystem = new \Rdb\System\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName);
            $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();

            // validate the form. -------------------------------------------------
            $formValidated = false;
            if (empty($data['folderrelpath'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Hack attempt!') . ' ' . d__('rdbcmsa', 'Could not delete the root folder.');
                http_response_code(400);
                $formValidated = false;
            } else {
                $formValidated = true;
            }

            if ($FilesSubController->isRestrictedFolder($data['folderrelpath'], $this->restrictedFolder)) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Unable to delete restricted folder.');
                http_response_code(400);
                $formValidated = false;
            }

            $folderToRenameRemovedUpper = str_replace(['../', '..\\', '...', '..'], '', $data['folderrelpath']);
            if ($data['folderrelpath'] !== $folderToRenameRemovedUpper) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Hack attempt!');
                http_response_code(400);
                $formValidated = false;
            }
            unset($folderToRenameRemovedUpper);

            if (!$FileSystem->isDir($data['folderrelpath'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'The selected folder is not exists, please check again.');
                http_response_code(400);
                $formValidated = false;
            }
            // end validate the form. --------------------------------------------

            if ($formValidated === true) {
                $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);

                try {
                    $deleteResult = $FilesDb->deleteFilesInFolder($data['folderrelpath']);

                    if ($deleteResult === true) {
                        $FileSystem->deleteFolder($data['folderrelpath'], true);
                        if ($FileSystem->isDir($data['folderrelpath'])) {
                            // if folder still exists.
                            $deleteResult = false;
                            $errorMessage = sprintf(
                                d__('rdbcmsa', 'Unable to delete selected folder. %s'),
                                PUBLIC_PATH . '/' . $this->rootPublicFolderName . '/' . $data['folderrelpath']
                            );

                            if ($this->Container->has('Logger')) {
                                /* @var $Logger \Rdb\System\Libraries\Logger */
                                $Logger = $this->Container->get('Logger');
                                $Logger->write('modules/cms/controllers/admin/files/folderscontroller', 3, $errorMessage . ' Deleted: {track_deleted}', ['track_deleted' => print_r($FileSystem->trackDeleted, true)]);
                                unset($Logger);
                            }

                            throw new \RuntimeException($errorMessage);
                        }
                    }
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage();
                    $output['errcatch'] = true;
                    $deleteResult = false;
                }

                if ($deleteResult === true) {
                    http_response_code(204);
                } else {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to delete.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }

                unset($deleteResult, $errorMessage);
            }

            unset($FilesSubController, $FileSystem);
        } else {
            // if unable to validate token.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
            http_response_code(400);
        }

        unset($csrfName, $csrfValue);
        // generate new token for re-submit the form continueously without reload the page.
        $output = array_merge($output, $Csrf->createToken());

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Csrf, $Url);
        return $this->responseAcceptType($output);
    }// doDeleteFolderAction


    /**
     * Get all folders and sub folders recursively form public/[root public folder name - default is rdbadmin-public].
     * 
     * @return string
     */
    public function doGetFoldersAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['list', 'add', 'edit', 'delete', 'move']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf([
            'persistentTokenMode' => true,
        ]);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getFilesUrlsMethod();

        if (
            isset($_GET[$csrfName]) &&
            isset($_GET[$csrfValue]) &&
            $Csrf->validateToken($_GET[$csrfName], $_GET[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            // create root public folder for upload if not exists.
            $FileSystem = new \Rdb\System\Libraries\FileSystem(PUBLIC_PATH);
            $FileSystem->createFolder($this->rootPublicFolderName);
            unset($FileSystem);
            $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
            $targetDir = PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName;

            // list folders and subs recursively to nested array.
            // @link https://stackoverflow.com/a/40616438/128761 Original source code.
            $RDI = new \RecursiveDirectoryIterator(
                $targetDir,
                \FilesystemIterator::SKIP_DOTS
            );
            $RII = new \RecursiveIteratorIterator(
                $RDI,
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );
            $RII = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterRestricted(
                $RII,
                $targetDir,
                $this->restrictedFolder
            );

            $output['list'] = [];
            $references = [&$output['list']];
            $i = 0;
            foreach ($RII as $filename => $File) {
                if ($File->isFile()) {
                    continue;
                }

                $relatePath = str_replace(
                    ['/', '\\', DIRECTORY_SEPARATOR],
                    '/',
                    str_replace(
                        $targetDir . DIRECTORY_SEPARATOR, 
                        '', 
                        $File->getPathname()
                    )
                );// $relatePath will be use when rename, delete folder.

                $file = [
                    'name' => $File->getFilename(),
                    'size' => $File->getSize(),
                    'realPath' => $File->getPathname(),
                    'relatePath' => $relatePath,
                    'depth' => $RII->getDepth(),
                ];

                if ($File->isDir() && $RDI->hasChildren()) {
                    $file['children'] = [];
                    $references[$RII->getDepth() + 1] =& $file['children'];
                }

                $references[$RII->getDepth()][] = $file;
                $i++;

                unset($file, $relatePath);
            }// endforeach;
            unset($File, $filename, $FilesSubController, $i, $RDI, $references, $RII, $targetDir);
        } else {
            // if unable to validate token.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
            http_response_code(400);
        }

        unset($csrfName, $csrfValue);
        // generate new token for re-submit the form continueously without reload the page.
        $output = array_merge($output, $Csrf->createToken());

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Csrf, $Url);
        return $this->responseAcceptType($output);
    }// doGetFoldersAction


    /**
     * Create new folder.
     * 
     * @return string
     */
    public function doNewFolderAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['list', 'add', 'edit', 'delete', 'move']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf([
            'persistentTokenMode' => true,
        ]);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getFilesUrlsMethod();

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            // prepare data
            $data = [];
            $data['new_folder_in'] = str_replace(['\\', '/', DIRECTORY_SEPARATOR], '/', trim($this->Input->post('new_folder_in')));
            $data['new_folder_in'] = trim($data['new_folder_in'], '/');
            $data['new_folder_name'] = $this->Input->post('new_folder_name');

            $FileSystem = new \Rdb\System\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName);

            // validate the form. -------------------------------------------------
            $formValidated = false;
            if (empty($data['new_folder_name'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Please enter the folder name.');
                http_response_code(400);
                $formValidated = false;
            } else {
                $formValidated = true;
            }

            $newFolderInRemovedUpper = str_replace(['../', '..\\', '...', '..'], '', $data['new_folder_in']);
            if ($data['new_folder_in'] !== $newFolderInRemovedUpper) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Hack attempt!');
                http_response_code(400);
                $formValidated = false;
            }
            unset($newFolderInRemovedUpper);

            if (!$FileSystem->isDir($data['new_folder_in'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'The target location to create new folder is not exists, please check again.');
                http_response_code(400);
                $formValidated = false;
            }

            $safeNewFolderName = $Url->removeUnsafeUrlCharacters($data['new_folder_name']);
            if ($safeNewFolderName !== $data['new_folder_name']) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = sprintf(d__('rdbcmsa', 'Please enter valid characters for new folder name. Suggested: %s'), '<code>' . $safeNewFolderName . '</code>');
                http_response_code(400);
                $formValidated = false;
            }
            unset($safeNewFolderName);

            if ($formValidated === true && $FileSystem->isDir($data['new_folder_in'] . '/' . $data['new_folder_name'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'The folder name is already exists.');
                http_response_code(400);
                $formValidated = false;
            }
            // end validate the form. --------------------------------------------

            if ($formValidated === true) {
                // if form validated and all passed.
                if ($FileSystem->createFolder($data['new_folder_in'] . '/' . $data['new_folder_name']) === true) {
                    // if create new folder successfully.
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'New folder was created successfully.');
                    http_response_code(201);
                } else {
                    // if failed to create new folder.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to create new folder.');
                    http_response_code(500);
                }
            }

            unset($FileSystem);
        } else {
            // if unable to validate token.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
            http_response_code(400);
        }

        unset($csrfName, $csrfValue);
        // generate new token for re-submit the form continueously without reload the page.
        $output = array_merge($output, $Csrf->createToken());

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Csrf, $Url);
        return $this->responseAcceptType($output);
    }// doNewFolderAction


    /**
     * Rename selected folder. Also rename in the DB.
     * 
     * @global array $_PATCH
     * @return string
     */
    public function doRenameFolderAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['list', 'add', 'edit', 'delete', 'move']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf([
            'persistentTokenMode' => true,
        ]);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getFilesUrlsMethod();

        // make patch data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validate csrf passed.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            // prepare data
            $data = [];
            $data['folder_to_rename'] = str_replace(['\\', '/', DIRECTORY_SEPARATOR], '/', trim($this->Input->patch('folder_to_rename')));// target/old-folder-name
            $data['folder_to_rename'] = trim($data['folder_to_rename'], '/');
            $data['new_folder_name'] = $this->Input->patch('new_folder_name');// new-folder-name

            $FileSystem = new \Rdb\System\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName);
            $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();

            // validate the form. -------------------------------------------------
            $formValidated = false;
            if (empty($data['new_folder_name'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Please enter the folder name.');
                http_response_code(400);
                $formValidated = false;
            } else {
                $formValidated = true;
            }

            if ($FilesSubController->isRestrictedFolder($data['folder_to_rename'], $this->restrictedFolder)) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Unable to rename restricted folder.');
                http_response_code(400);
                $formValidated = false;
            }

            $folderToRenameRemovedUpper = str_replace(['../', '..\\', '...', '..'], '', $data['folder_to_rename']);
            if ($data['folder_to_rename'] !== $folderToRenameRemovedUpper) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Hack attempt!');
                http_response_code(400);
                $formValidated = false;
            }
            unset($folderToRenameRemovedUpper);

            if (!$FileSystem->isDir($data['folder_to_rename'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'The selected folder is not exists, please check again.');
                http_response_code(400);
                $formValidated = false;
            }

            $safeNewFolderName = $Url->removeUnsafeUrlCharacters($data['new_folder_name']);
            if ($safeNewFolderName !== $data['new_folder_name']) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = sprintf(d__('rdbcmsa', 'Please enter valid characters for new folder name. Suggested: %s'), '<code>' . $safeNewFolderName . '</code>');
                http_response_code(400);
                $formValidated = false;
            }
            unset($safeNewFolderName);

            $upperFolder = trim(dirname($data['folder_to_rename']), '\\/');
            if ($upperFolder === '.') {
                $upperFolder = '';
            } else {
                $upperFolder .= DIRECTORY_SEPARATOR;
            }
            $newFolderName = PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName . DIRECTORY_SEPARATOR . $upperFolder . $data['new_folder_name'];
            if ($FileSystem->isDir($upperFolder . $data['new_folder_name'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = sprintf(d__('rdbcmsa', 'The folder name is already exists. (%s)'), '<code>' . $newFolderName . '</code>');
                http_response_code(400);
                $formValidated = false;
            }
            // end validate the form. --------------------------------------------

            if ($formValidated === true) {
                $oldFolderName = PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName . DIRECTORY_SEPARATOR . $data['folder_to_rename'];
                // if form validated and all passed.
                if (rename($oldFolderName, $newFolderName) === true) {
                    // if create new folder successfully.
                    // rename `file_folder` in `files` table.
                    $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);
                    $upperFolder = trim($upperFolder, '\\/');
                    if (!empty($upperFolder)) {
                        $upperFolder .= '/';
                    }
                    $FilesDb->renameFolder($data['folder_to_rename'], $upperFolder . $data['new_folder_name']);
                    // rename `revision_body_value`, `revision_body_summary` in `post_revision` table.
                    $publicRootUrl = $Url->getDomainProtocol() . $Url->getPublicUrl() . '/' . $this->rootPublicFolderName;
                    $FilesDb->renameFileFolderInPostRevision($publicRootUrl . '/' . $data['folder_to_rename'], $publicRootUrl . '/' . $upperFolder . $data['new_folder_name']);
                    unset($FilesDb, $publicRootUrl);
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Success.');
                    http_response_code(200);
                } else {
                    // if failed to create new folder.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to rename folder.');
                    http_response_code(500);
                }
                unset($oldFolderName);
            }

            unset($FilesSubController, $FileSystem, $newFolderName, $upperFolder);
        } else {
            // if unable to validate token.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
            http_response_code(400);
        }

        unset($csrfName, $csrfValue);
        // generate new token for re-submit the form continueously without reload the page.
        $output = array_merge($output, $Csrf->createToken());

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Csrf, $Url);
        return $this->responseAcceptType($output);
    }// doRenameFolderAction


}
