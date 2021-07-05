<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Files;


/**
 * Move action controller.
 * 
 * @since 0.0.8
 */
class MoveController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\FilesTrait;


    use Traits\FilesActionsTrait;


    /**
     * Move file(s) to new location.
     * 
     * @param string $file_ids
     * @return string
     */
    public function doMoveAction(string $file_ids): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['move']);

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

        if (!isset($_POST['action']) || strtolower($_POST['action']) !== 'move') {
            // if no action, don't waste time.
            return '';
        }

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_POST[$csrfName], $_POST[$csrfValue]);

            $moveTargetFolder = str_replace(
                ['/', '\\', DIRECTORY_SEPARATOR], 
                '/', 
                trim($this->Input->post('move-target-folder'))
            );
            $moveTargetFolder = trim($moveTargetFolder, '/');

            // validate file and action must be selected.
            $validateFileActions = $this->validateFileActions(
                $file_ids, 
                $_POST['action']
            );

            if (isset($validateFileActions['formValidated']) && $validateFileActions['formValidated'] === true) {
                // if form validation passed.
                $formValidated = true;
            } else {
                // if form validation failed.
                if (isset($validateFileActions['formResultStatus']) && isset($validateFileActions['formResultMessage'])) {
                    $output['formResultStatus'] = $validateFileActions['formResultStatus'];
                    $output['formResultMessage'] = $validateFileActions['formResultMessage'];
                }
            }

            if (isset($formValidated) && $formValidated === true) {
                if (is_array($validateFileActions['listSelectedFiles']['items'])) {
                    $PDO = $this->Db->PDO();
                    $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);
                    $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
                    $FilesSubController->Container = $this->Container;
                    $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName);
                    $Image = new \Rdb\Modules\RdbCMSA\Libraries\Image('');
                    $Image->Container = $this->Container;
                    $output['moveFilesLog'] = [];
                    $publicRootUrl = $Url->getDomainProtocol() . $Url->getPublicUrl() . '/' . $this->rootPublicFolderName;

                    try {
                        $thumbnailSizes = $FilesSubController->getThumbnailSizes();

                        foreach ($validateFileActions['listSelectedFiles']['items'] as $row) {
                            // each file has set (original, main, resized). 
                            // and each set file there is an update command to replace url in the table.
                            // so, the transaction will be for each file only.
                            $PDO->beginTransaction();

                            if ($row->file_visibility !== '1') {
                                // if file visibility is NOT in public. to move these files, please create another controller because it is for different purpose.
                                $output['moveFilesLog'][$row->file_id]['skipped'] = true;
                                $output['moveFilesLog'][$row->file_id]['skippedReason'] = 'not_public';
                                continue;
                            }

                            if ($row->file_folder === $moveTargetFolder) {
                                // if target folder is same as currently it is.
                                // skip it.
                                $output['moveFilesLog'][$row->file_id]['skipped'] = true;
                                $output['moveFilesLog'][$row->file_id]['skippedReason'] = 'target_is_current';
                                continue;
                            }

                            // each file has set (original or backup before watermark file, main file, resized files)
                            $fileSetToMove = [];
                            $fileSetMoved = [];

                            // get main file.
                            $fileRelPath = $FilesDb->getFileRelatePath($row);
                            $output['moveFilesLog'][$row->file_id]['fileRelPath'] = $fileRelPath;
                            $output['moveFilesLog'][$row->file_id]['fileFullPath'] = $FileSystem->getFullPathWithRoot($fileRelPath);
                            $fileSetToMove[] = $fileRelPath;

                            // search original file.
                            $originalFile = $Image->searchOriginalFile(
                                $FileSystem->getFullPathWithRoot($fileRelPath),
                                [
                                    'returnFullPath' => false,
                                    'relateFrom' => PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName,
                                ]
                            );
                            if (false !== $originalFile) {
                                $output['moveFilesLog'][$row->file_id]['_original'] = $originalFile;
                                $fileSetToMove[] = $originalFile;
                            }// endif; original not false
                            unset($originalFile);

                            // loop get thumbnails.
                            foreach ($thumbnailSizes as $name => list($width, $height)) {
                                $thumbnailFile = $FileSystem->addSuffixFileName($fileRelPath, '_' . $name);
                                $output['moveFilesLog'][$row->file_id]['_' . $name] = $thumbnailFile;
                                $fileSetToMove[] = $thumbnailFile;
                                unset($thumbnailFile);
                            }// endforeach; thumbnails
                            unset($height, $name, $width);

                            // begins move file set.
                            foreach ($fileSetToMove as $eachFile) {
                                if ($FileSystem->isFile($eachFile)) {
                                    $fileExp = explode('/', str_replace(['/', '\\', DIRECTORY_SEPARATOR], '/', $eachFile));
                                    $filenameExt = $fileExp[count($fileExp) - 1];
                                    unset($fileExp);

                                    $newLocation = ltrim($moveTargetFolder . '/' . $filenameExt, '/');
                                    $moveResult = $FileSystem->rename($eachFile, $newLocation);
                                    $output['moveFilesLog'][$row->file_id][$eachFile] = $FileSystem->getFullPathWithRoot($newLocation);

                                    if ($moveResult === false) {
                                        // if failed to move.
                                        // restore moved file back to its old place.
                                        $this->restoreFilesMoved($FileSystem, $fileSetMoved);
                                        // rollback previously updated for the set.
                                        $PDO->rollBack();
                                        // throw exception to break the loop and let user know about the error.
                                        throw new \Exception(sprintf(d__('rdbcmsa', 'Unable to move file %1$s to %2$s.'), $eachFile, $newLocation));
                                    } else {
                                        // if moved success.
                                        $fileSetMoved[] = [
                                            $eachFile,
                                            $newLocation
                                        ];

                                        // replace URL of this file set in db.
                                        $currentUrl = $publicRootUrl . ($row->file_folder !== '' ? '/' . $row->file_folder : '') . '/' . $filenameExt;
                                        $newUrl = $publicRootUrl . ($moveTargetFolder !== '' ? '/' . $moveTargetFolder : '') . '/' . $filenameExt;
                                        $FilesDb->renameFileFolderInPostRevision($currentUrl, $newUrl);
                                        unset($currentUrl, $newUrl);
                                    }
                                    unset($moveResult, $newLocation);
                                }
                            }// endforeach;
                            unset($eachFile);
                            // end move file set.

                            // update to db.
                            $FilesDb->updateFolderName(
                                $moveTargetFolder,
                                [
                                    'file_id' => $row->file_id,
                                ]
                            );
                            // to this line, it means everything is successfully. commit the change on db.
                            $PDO->commit();

                            unset($fileSetMoved, $fileSetToMove);
                        }// endforeach;
                        unset($row);

                        $moveSuccess = true;
                        unset($thumbnailSizes);
                    } catch (\Exception $ex) {
                        $output['errorMessage'] = $ex->getMessage();
                        $moveSuccess = false;
                    }// endtry;

                    unset($FilesDb, $FilesSubController, $FileSystem, $Image, $PDO, $publicRootUrl);

                    if (isset($moveSuccess) && $moveSuccess === true) {
                        // if moved success.
                        if ($this->Container->has('Logger') && isset($output['moveFilesLog'])) {
                            /* @var $Logger \Rdb\System\Libraries\Logger */
                            $Logger = $this->Container->get('Logger');
                            $Logger->write('modules/cms/controllers/admin/files/movecontroller', 0, 'The files have been moved.', ['files' => $output['moveFilesLog']]);
                            unset($Logger);
                        }

                        $output['formResultStatus'] = 'success';
                        $output['formResultMessage'] = d__('rdbcmsa', 'Moved successfully.');

                        $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                        unset($output['formResultMessage'], $output['formResultStatus']);

                        http_response_code(200);

                        $output['redirectBack'] = $output['urls']['getFilesUrl'];
                    } else {
                        // if move failed.
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = d__('rdbcmsa', 'Unable to move.');
                        if (isset($output['errorMessage'])) {
                            $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                        }
                        http_response_code(500);
                    }
                }// endif; $validateFileActions['listSelectedFiles']['items']
            }// endif; form validation passed.

            unset($formValidated, $validateFileActions);
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
    }// doMoveAction


    /**
     * Restore, move back from new to its old location.
     * 
     * @param \Rdb\System\Libraries\FileSystem $FileSystem
     * @param array $filesMoved
     */
    protected function restoreFilesMoved(\Rdb\System\Libraries\FileSystem $FileSystem, array $filesMoved)
    {
        foreach ($filesMoved as list($oldLocation, $newLocation)) {
            $FileSystem->rename($newLocation, $oldLocation);
        }

        unset($newLocation, $oldLocation);
    }// restoreFilesMoved


}
