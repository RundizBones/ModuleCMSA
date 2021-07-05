<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Files;


/**
 * Files actions controller.
 * 
 * @since 0.0.1
 */
class ActionsController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\FilesTrait;


    use Traits\FilesActionsTrait;


    /**
     * Do delete the data in db.
     * 
     * @global array $_DELETE
     * @param string $file_ids
     * @return string
     */
    public function doDeleteAction(string $file_ids): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['delete']);

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

        // make delete data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (!isset($_DELETE['action'])) {
            // if no action
            // don't waste time on this.
            return '';
        }

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            // validate file and action must be selected.
            $validateFileActions = $this->validateFileActions(
                $file_ids, 
                $_DELETE['action']
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
                $PDO = $this->Db->PDO();
                $PDO->beginTransaction();
                $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);
                $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName);
                $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
                $FilesSubController->Container = $this->Container;
                $Image = new \Rdb\Modules\RdbCMSA\Libraries\Image('');
                $Image->Container = $this->Container;

                if (is_array($validateFileActions['listSelectedFiles']['items'])) {
                    $deleteSuccess = false;
                    $output['deleteFilesLog'] = [];
                    try {
                        $thumbnailSizes = $FilesSubController->getThumbnailSizes();
                        foreach ($validateFileActions['listSelectedFiles']['items'] as $row) {
                            // get main file.
                            $fileRelPath = $FilesDb->getFileRelatePath($row);
                            $output['deleteFilesLog'][$row->file_id]['fileRelPath'] = $fileRelPath;
                            $output['deleteFilesLog'][$row->file_id]['fileFullPath'] = $FileSystem->getFullPathWithRoot($fileRelPath);
                            // delete original (backup) file.
                            $originalFile = $Image->searchOriginalFile(
                                $FileSystem->getFullPathWithRoot($fileRelPath),
                                [
                                    'returnFullPath' => false,
                                    'relateFrom' => PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName,
                                ]
                            );
                            $output['deleteFilesLog'][$row->file_id]['_original'] = $originalFile;
                            if (false !== $originalFile && $FileSystem->isFile($originalFile)) {
                                $deleteResult = $FileSystem->deleteFile($originalFile);
                                $output['deleteFilesLog'][$row->file_id]['_original' . 'IsFileAndDeleted'] = var_export($deleteResult, true);
                            }
                            // loop delete thumbnails.
                            foreach ($thumbnailSizes as $name => list($width, $height)) {
                                $thumbnailFile = $FileSystem->addSuffixFileName($fileRelPath, '_' . $name);
                                $output['deleteFilesLog'][$row->file_id]['_' . $name] = $thumbnailFile;
                                if ($FileSystem->isFile($thumbnailFile)) {
                                    $deleteResult = $FileSystem->deleteFile($thumbnailFile);
                                    $output['deleteFilesLog'][$row->file_id]['_' . $name . 'IsFileAndDeleted'] = var_export($deleteResult, true);
                                }
                            }// endforeach;
                            unset($height, $name, $width);
                            // end loop delete thumbnails.
                            // delete main file.
                            if ($FileSystem->isFile($fileRelPath)) {
                                $deleteResult = $FileSystem->deleteFile($fileRelPath);
                                $output['deleteFilesLog'][$row->file_id]['fileRelPathDeleted'] = var_export($deleteResult, true);
                            }
                            // delete from DB.
                            $FilesDb->deleteAFile(['file_id' => $row->file_id]);

                            unset($deleteResult, $fileRelPath);
                        }// endforeach;
                        unset($row);
                        $deleteSuccess = true;
                        unset($thumbnailSizes);
                    } catch (\Exception $ex) {
                        $output['errorMessage'] = $ex->getMessage();
                        $PDO->rollBack();
                        $deleteSuccess = false;
                    }

                    if ($deleteSuccess === true) {
                        $PDO->commit();

                        if ($this->Container->has('Logger') && isset($output['deleteFilesLog'])) {
                            /* @var $Logger \Rdb\System\Libraries\Logger */
                            $Logger = $this->Container->get('Logger');
                            $Logger->write('modules/cms/controllers/admin/files/actionscontroller', 0, 'The files was deleted.', ['files' => $output['deleteFilesLog']]);
                            unset($Logger);
                        }
                        $output['formResultStatus'] = 'success';
                        $output['formResultMessage'] = __('Deleted successfully.');

                        $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                        unset($output['formResultMessage'], $output['formResultStatus']);

                        http_response_code(204);

                        $output['redirectBack'] = $output['urls']['getFilesUrl'];
                    } else {
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = d__('rdbcmsa', 'Unable to delete.');
                        if (isset($output['errorMessage'])) {
                            $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                        }
                        http_response_code(500);
                    }
                }// endif; $validateFileActions['listSelectedFiles']['items']

                unset($FilesDb, $FilesSubController, $FileSystem, $Image, $PDO);
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
    }// doDeleteAction


    /**
     * Update files data.
     * 
     * @global array $_PATCH
     * @param string $file_ids
     * @param string $action
     * @return string
     */
    public function doUpdateDataAction(string $file_ids, string $action): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['edit']);

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

        // make delete data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (!isset($_PATCH['action'])) {
            // if no action
            // don't waste time on this.
            return '';
        }

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            if ($action === 'setwatermark') {
                // if action is set watermark.
                $output = array_merge($output, $this->doUpdateDataSetWatermark($file_ids, $action));
                $output = array_merge($output, $this->doUpdateDataResizeThumbnails($file_ids, $action));
            } elseif ($action === 'removewatermark') {
                // if action is remove watermark.
                $output = array_merge($output, $this->doUpdateDataRemoveWatermark($file_ids, $action));
                $output = array_merge($output, $this->doUpdateDataResizeThumbnails($file_ids, $action));
            } elseif ($action === 'updatemeta') {
                // if action is update metadata.
                $output = array_merge($output, $this->doUpdateDataUpdatemeta($file_ids, $action));
            } elseif ($action === 'updatethumbnails') {
                // if action is update thumbnails.
                $output = array_merge($output, $this->doUpdateDataResizeThumbnails($file_ids, $action));
            }
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
    }// doUpdateDataAction


    /**
     * Remove watermark.
     * 
     * @param string $file_ids
     * @param string $action
     * @return array
     */
    protected function doUpdateDataRemoveWatermark(string $file_ids, string $action): array
    {
        $output = [];

        $fileIdArray = explode(',', $file_ids);
        if (is_array($fileIdArray)) {
            $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);
            $options = [];
            $options['file_id_in'] = $fileIdArray;
            $options['unlimited'] = true;
            $options['getFileFullPath'] = true;
            $result = $FilesDb->listItems($options);
            unset($options);

            $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
            $FilesSubController->Container = $this->Container;
            $Image = new \Rdb\Modules\RdbCMSA\Libraries\Image('');
            $Image->Container = $this->Container;
            $applied = 0;
            $notImageFiles = [];

            if (isset($result['items']) && is_array($result['items'])) {
                foreach ($result['items'] as $row) {
                    if (in_array(strtolower($row->file_ext), $FilesSubController->imageExtensions)) {
                        // if one of selected files is an image.
                        $Image->removeWatermark($row->fileFullPath);
                        $applied++;
                    } else {
                        // if it is not an image.
                        $output['formResultStatus'] = 'warning';
                        $notImageFiles[] = $row->file_original_name;
                    }
                }// endforeach;
                unset($row);
            }// endif; if there is selected items.
            unset($result);

            // set output result for alert box.
            if (!isset($output['formResultStatus'])) {
                $output['formResultStatus'] = 'success';
            }
            if (!empty($notImageFiles)) {
                $output['formResultMessage'][] = sprintf(
                    dn__('rdbcmsa', 'The selected file is not an image. (%1$s)', 'The selected files are not images. (%1$s)', count($notImageFiles)),
                    implode(', ', $notImageFiles)
                );
            }
            if (!empty($applied)) {
                $output['formResultMessage'][] = d__('rdbcmsa', 'Updated successfully.');
            }
            $output['totalResized'] = $applied;
            http_response_code(200);
            unset($applied, $notImageFiles);

            unset($FilesDb, $FilesSubController, $Image);
        }// endif; is array fileIdArray
        unset($fileIdArray);

        return $output;
    }// doUpdataDateRemoveWatermark


    /**
     * Resize thumbnails.
     * 
     * @param string $file_ids
     * @param string $action
     * @return array
     */
    protected function doUpdateDataResizeThumbnails(string $file_ids, string $action): array
    {
        $output = [];

        $fileIdArray = explode(',', $file_ids);
        if (is_array($fileIdArray)) {
            $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);
            $options = [];
            $options['file_id_in'] = $fileIdArray;
            $options['unlimited'] = true;
            $options['getFileFullPath'] = true;
            $result = $FilesDb->listItems($options);
            unset($options);

            $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH);
            $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
            $Image = new \Rdb\Modules\RdbCMSA\Libraries\Image('');
            $Image->Container = $this->Container;
            $resized = 0;
            $notImageFiles = [];

            if (isset($result['items']) && is_array($result['items'])) {
                foreach ($result['items'] as $row) {
                    if (in_array(strtolower($row->file_ext), $FilesSubController->imageExtensions)) {
                        // if one of selected files is an image.
                        $Image->resizeThumbnails($row->fileFullPath);
                        $resized++;
                    } else {
                        // if it is not an image.
                        $output['formResultStatus'] = 'warning';
                        $notImageFiles[] = $row->file_original_name;
                    }
                }// endforeach;
                unset($row);
            }// endif; if there is selected items.
            unset($result);

            // set output result for alert box.
            if (!isset($output['formResultStatus'])) {
                $output['formResultStatus'] = 'success';
            }
            if (!empty($notImageFiles)) {
                $output['formResultMessage'][] = sprintf(
                    dn__('rdbcmsa', 'The selected file is not an image. (%1$s)', 'The selected files are not images. (%1$s)', count($notImageFiles)),
                    implode(', ', $notImageFiles)
                );
            }
            if (!empty($resized)) {
                $output['formResultMessage'][] = d__('rdbcmsa', 'Updated successfully.');
            }
            $output['totalResized'] = $resized;
            http_response_code(200);
            unset($notImageFiles, $resized);

            unset($FilesDb, $FilesSubController, $FileSystem, $Image);
        }// endif; is array fileIdArray
        unset($fileIdArray);

        return $output;
    }// doUpdateDataResizeThumbnails


    /**
     * Set watermark.
     * 
     * @param string $file_ids
     * @param string $action
     * @return array
     */
    protected function doUpdateDataSetWatermark(string $file_ids, string $action): array
    {
        $output = [];

        $fileIdArray = explode(',', $file_ids);
        if (is_array($fileIdArray)) {
            $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);
            $options = [];
            $options['file_id_in'] = $fileIdArray;
            $options['unlimited'] = true;
            $options['getFileFullPath'] = true;
            $result = $FilesDb->listItems($options);
            unset($options);

            $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH);
            $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
            $FilesSubController->Container = $this->Container;
            $Image = new \Rdb\Modules\RdbCMSA\Libraries\Image('');
            $Image->Container = $this->Container;
            $applied = 0;
            $notImageFiles = [];

            if (isset($result['items']) && is_array($result['items'])) {
                foreach ($result['items'] as $row) {
                    if (in_array(strtolower($row->file_ext), $FilesSubController->imageExtensions)) {
                        // if one of selected files is an image.
                        $Image->setWatermark($row->fileFullPath);
                        $applied++;
                    } else {
                        // if it is not an image.
                        $output['formResultStatus'] = 'warning';
                        $notImageFiles[] = $row->file_original_name;
                    }
                }// endforeach;
                unset($row);
            }// endif; if there is selected items.
            unset($result);

            // set output result for alert box.
            if (!isset($output['formResultStatus'])) {
                $output['formResultStatus'] = 'success';
            }
            if (!empty($notImageFiles)) {
                $output['formResultMessage'][] = sprintf(
                    dn__('rdbcmsa', 'The selected file is not an image. (%1$s)', 'The selected files are not images. (%1$s)', count($notImageFiles)),
                    implode(', ', $notImageFiles)
                );
            }
            if (!empty($applied)) {
                $output['formResultMessage'][] = d__('rdbcmsa', 'Updated successfully.');
            }
            $output['totalResized'] = $applied;
            http_response_code(200);
            unset($applied, $notImageFiles);

            unset($FilesDb, $FilesSubController, $FileSystem, $Image);
        }// endif; is array fileIdArray
        unset($fileIdArray);

        return $output;
    }// doUpdateDataSetWatermark


    /**
     * Update metadata of selected files.
     * 
     * Send http status code if success.
     * 
     * @param string $file_ids
     * @param string $action
     * @return array
     */
    private function doUpdateDataUpdatemeta(string $file_ids, string $action): array
    {
        $output = [];

        $fileIdArray = explode(',', $file_ids);
        if (is_array($fileIdArray)) {
            $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);
            $options = [];
            $options['file_id_in'] = $fileIdArray;
            $options['unlimited'] = true;
            $options['getFileFullPath'] = true;
            $result = $FilesDb->listItems($options);
            unset($options);

            $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem();
            $updated = 0;

            if (isset($result['items']) && is_array($result['items'])) {
                foreach ($result['items'] as $row) {
                    $data = [];
                    if (stripos($row->file_mime_type, 'image/') !== false) {
                        $data['file_metadata'] = json_encode(
                            [
                                'image' => $FileSystem->getImageMetadata($row->fileFullPath, ['fullPath' => true]),
                            ]
                        );
                    } elseif (stripos($row->file_mime_type, 'video/') !== false) {
                        $data['file_metadata'] = json_encode(
                            [
                                'video' => $FileSystem->getVideoMetadata($row->fileFullPath, ['fullPath' => true]),
                                'audio' => $FileSystem->getAudioMetadata($row->fileFullPath, ['fullPath' => true]),
                            ]
                        );
                    } elseif (stripos($row->file_mime_type, 'audio/') !== false) {
                        $data['file_metadata'] = json_encode(
                            [
                                'audio' => $FileSystem->getAudioMetadata($row->fileFullPath, ['fullPath' => true]),
                            ]
                        );
                    } else {
                        $data['file_metadata'] = null;
                    }
                    if (isset($data['file_metadata']) && empty($data['file_metadata']) && !is_null($data['file_metadata'])) {
                        unset($data['file_metadata']);
                    }
                    if (!empty($data)) {
                        $updateResult = $FilesDb->update($data, ['file_id' => $row->file_id]);
                        if ($updateResult === true) {
                            $updated++;
                        }
                        unset($updateResult);
                    }
                }// endforeach;
                unset($row);
            }// endif; if there is selected items.
            unset($result);

            $output['formResultStatus'] = 'success';
            $output['formResultMessage'] = d__('rdbcmsa', 'Updated successfully.');
            $output['totalUpdated'] = $updated;
            http_response_code(200);
            unset($updated);

            unset($FilesDb, $FileSystem);
        }// endif; is array fileIdArray
        unset($fileIdArray);

        return $output;
    }// doUpdateDataUpdatemeta


    /**
     * Display bulk actions page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['edit', 'delete', 'move']);

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
        $output['pageTitle'] = __('Confirmation required');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $output['urls'] = $this->getFilesUrlsMethod();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        // validate files and action must be selected.
        $output = array_merge(
            $output, 
            $this->validateFileActions(
                $this->Input->get('file_ids'), 
                $this->Input->get('action')
            )
        );

        if (
            isset($output['formValidated']) && 
            $output['formValidated'] === true && 
            $this->Input->get('action') === 'move'
        ) {
            // list folders for select.
            $output['rootPublicFolder'] = $this->rootPublicFolderName;
            $folders = $this->listFolders();
            $output['folders'] = ($folders);
            unset($folders);
        }

        $urlBaseWithLang = $Url->getAppBasedPath(true);
        $output['breadcrumb'] = [
            [
                'item' => __('Admin home'),
                'link' => $urlBaseWithLang . '/admin',
            ],
            [
                'item' => d__('rdbcmsa', 'Contents'),
                'link' => $urlBaseWithLang . '/admin/cms/posts',
            ],
            [
                'item' => d__('rdbcmsa', 'Files'),
                'link' => $output['urls']['getFilesUrl'],
            ],
            [
                'item' => $output['pageTitle'],
                'link' => $output['urls']['actionsFilesUrl'],
            ],
        ];
        unset($urlBaseWithLang);

        $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            // get RdbAdmin module's assets data for render page correctly.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            // get module's assets
            $ModuleAssets = new \Rdb\Modules\RdbCMSA\ModuleData\ModuleAssets($this->Container);
            $moduleAssetsData = $ModuleAssets->getModuleAssets();
            unset($ModuleAssets);
            // Assets class for add CSS and JS.
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            // add CSS and JS assets to make basic functional and style on admin page works correctly.
            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage'], $Assets->mergeAssetsData('css', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addMultipleAssets('js', ['rdbcmsaFilesActionsAction', 'rdbaHistoryState'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaFilesActionsAction',
                'RdbCMSAFilesCommonObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'audioExtensions' => $FilesSubController->audioExtensions,
                    'imageExtensions' => $FilesSubController->imageExtensions,
                    'videoExtensions' => $FilesSubController->videoExtensions,
                    'txtDeleteSuccessfully' => __('Deleted successfully.'),
                ], $this->getFilesUrlsMethod())
            );

            // include html functions file to use `renderBreadcrumbHtml()` function.
            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Files/actions_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $FilesSubController, $rdbAdminAssets);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


    /**
     * List *rdbadmin-public* sub folders into 2D array.
     * 
     * @return array
     */
    protected function listFolders(): array
    {
        $output = [];

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
        $RII = new \Rdb\Modules\RdbCMSA\Libraries\SPLIterators\FilterRestricted(
            $RII,
            $targetDir,
            $this->restrictedFolder
        );

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
            );

            $file = [
                'name' => $File->getFilename(),
                'size' => $File->getSize(),
                'realPath' => $File->getPathname(),
                'relatePath' => $relatePath,
                'depth' => $RII->getDepth(),
            ];

            $output[] = $file;

            unset($file, $relatePath);
        }// endforeach;
        unset($File, $filename, $RDI, $RII, $targetDir);

        return $output;
    }// listFolders


}
