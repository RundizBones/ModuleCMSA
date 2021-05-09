<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Files;


/**
 * Scan unindexed files controller.
 * 
 * @since 0.0.1
 */
class ScanUnindexedController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\FilesTrait;


    protected $scanMaxFilesATime = 10;


    /**
     * Do index selected items.
     * 
     * @return string
     */
    public function doIndexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['add']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf([
            'persistentTokenMode' => true,
        ]);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];

        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);
        if ($Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])) {
            // if validate token passed.
            unset($_POST[$csrfName], $_POST[$csrfValue]);

            if (
                is_array($this->Input->post('realPathHash')) && 
                isset($_POST['file_folder']) &&
                isset($_POST['file_name']) &&
                isset($_POST['realPath'])
            ) {
                // if there is at least one selected item.
                $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container);
                $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName);
                $Finfo = new \finfo();

                $totalFiles = count($_POST['realPathHash']);
                $alreadyIndexed = [];
                $fileNotExists = [];
                $success = [];
                $successHash = [];
                $insertedArray = [];
                foreach ($this->Input->post('realPathHash') as $index => $value) {
                    // check for correct format data.
                    if (
                        !array_key_exists($index, $_POST['file_folder']) ||
                        !array_key_exists($index, $_POST['file_name']) ||
                        !array_key_exists($index, $_POST['realPath'])
                    ) {
                        // if incorrect POST data format.
                        $fileNotExists[] = var_export($_POST['realPath'], true);
                        continue;
                    }

                    // check for file exists and hash must be correct.
                    if (
                        !is_file($_POST['realPath'][$index]) || 
                        sha1($_POST['realPath'][$index]) !== $value
                    ) {
                        // if file is not exists.
                        $fileNotExists[] = $_POST['realPath'][$index];
                        continue;
                    }

                    // check for file must not indexed.
                    $result = $FilesDb->get([
                        'file_folder' => $_POST['file_folder'][$index],
                        'file_name' => $_POST['file_name'][$index],
                    ]);
                    if (is_object($result) && !empty($result)) {
                        // if found selected item in DB.
                        $alreadyIndexed[] = $_POST['realPath'][$index];
                        unset($result);
                        continue;
                    }
                    unset($result);

                    // add selected file to db. ------------------------------------------
                    $expFile = explode('.', $_POST['file_name'][$index]);
                    $fileExtension = $expFile[count($expFile) - 1];
                    unset($expFile);

                    // rename the file.
                    $safeName = $FileSystem->setWebSafeFileName($_POST['file_name'][$index]);
                    if ($safeName === '.' . $fileExtension) {
                        $safeName = uniqid().'-'.str_replace('.', '', microtime(true)) . '.' . $fileExtension;
                    }
                    $oldName = (!empty($_POST['file_folder'][$index]) ? $_POST['file_folder'][$index] . DIRECTORY_SEPARATOR : '') . $_POST['file_name'][$index];
                    $newName = (!empty($_POST['file_folder'][$index]) ? $_POST['file_folder'][$index] . DIRECTORY_SEPARATOR : '') . $safeName;

                    if ($oldName !== $newName) {
                        $renameResult = $FileSystem->rename($oldName, $newName);
                    }

                    if (!isset($renameResult) || $renameResult === true) {
                        $data['file_folder'] = $_POST['file_folder'][$index];
                        $data['file_visibility'] = 1;
                        $data['file_name'] = $safeName;
                        $data['file_original_name'] = $_POST['file_name'][$index];
                        $data['file_mime_type'] = $Finfo->file($FileSystem->getFullPathWithRoot($newName), FILEINFO_MIME_TYPE);
                        $data['file_ext'] = $fileExtension;
                        $data['file_size'] = filesize($FileSystem->getFullPathWithRoot($newName));
                        $data['file_media_name'] = strip_tags($newName);
                        $insertId = $FilesDb->add($data);
                        if ($insertId !== false && $insertId > 0) {
                            $success[] = $FileSystem->getFullPathWithRoot($newName);
                            $successHash[] = $value;
                            $insertedArray[] = [
                                'extension' => $data['file_ext'],
                                'file_id' => $insertId,
                                'full_path_new_name' => $FileSystem->getFullPathWithRoot($newName),
                                'mime' => $data['file_mime_type'],
                                'new_name' => $safeName,
                            ];
                        }
                        unset($data, $fileExtension, $insertId);
                    } else {
                        $output['cannot_rename'] = true;
                        $output['oldName_newName'] = $oldName . ' :: ' . $newName;
                        $fileNotExists[] = $_POST['realPath'][$index];
                    }// endif; rename successfully.
                    unset($newName, $oldName, $renameResult, $safeName);
                    // end add selected file to db. --------------------------------------
                }// endforeach;
                unset($FilesDb, $Finfo, $index, $value);

                if (!empty($alreadyIndexed) || !empty($fileNotExists)) {
                    $message = '';
                    if (!empty($alreadyIndexed)) {
                        $message .= ' ' . d__('rdbcmsa', 'Already indexed files: %1$s.', implode(', ', $alreadyIndexed));
                    }
                    if (!empty($fileNotExists)) {
                        $message .= ' ' . d__('rdbcmsa', 'Files not exists: %1$s.', implode(', ', $fileNotExists));
                    }
                }

                if (!empty($success) && empty($alreadyIndexed) && empty($fileNotExists)) {
                    // if success only.
                    http_response_code(201);
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = dn__('rdbcmsa', 'The file was indexed successfully.', 'The files was indexed successfully.', $totalFiles);
                } elseif (!empty($success) && (!empty($alreadyIndexed) || !empty($fileNotExists))) {
                    // if success but contains error.
                    http_response_code(201);
                    $output['formResultStatus'] = 'warning';
                    $output['formResultMessage'] = d__('rdbcmsa', 'The selected files was indexed but some files are not.%1$s', ($message ?? ''));
                } else {
                    // if errors with no success.
                    if (!empty($alreadyIndexed) && empty($fileNotExists)) {
                        http_response_code(409);
                    } elseif (empty($alreadyIndexed) && !empty($fileNotExists)) {
                        http_response_code(404);
                    } else {
                        http_response_code(400);
                    }
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'There are errors with selected files.%1$s', ($message ?? ''));
                }

                $output['indexAllResult'] = [
                    'alreadyIndexed' => $alreadyIndexed,
                    'fileNotExists' => $fileNotExists,
                    'success' => $success,
                    'successHash' => $successHash,
                ];

                if (isset($insertedArray) && !empty($insertedArray)) {
                    $AddController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\Files\AddController($this->Container);
                    $AddController->updateMetadata($insertedArray);
                    $AddController->resizeImages($insertedArray, $FileSystem);
                    unset($AddController);
                }

                unset($alreadyIndexed, $fileNotExists, $FileSystem, $insertedArray, $message, $success, $successHash, $totalFiles);
            } else {
                // if there are no required POST data.
                http_response_code(400);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = d__('rdbcmsa', 'Please select items to index.');
            }
        } else {
            // if unable to validate token.
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
            $output = array_merge($output, $Csrf->createToken());
        }

        unset($csrfName, $csrfValue);
        // display, response part ---------------------------------------------------------------------------------------------
        unset($Csrf, $Url);
        return $this->responseAcceptType($output);
    }// doIndexAction


    /**
     * Scan file system to build array data of files in limited amount.
     * 
     * This method was called from `doScanUnindexedFiles()`.
     * 
     * @return array
     */
    private function doScanFileSystem(): array
    {
        $output = [];

        $FoldersController = new FoldersController($this->Container);
        $offset = trim($this->Input->get('offset', 0));
        $targetDir = PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName;

        $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
        $thumbnailSizes = $FilesSubController->getThumbnailSizes();
        unset($FilesSubController);
        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem($targetDir);
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $RDI = new \RecursiveDirectoryIterator(
            $targetDir,
            \FilesystemIterator::SKIP_DOTS
        );
        $RII = new \RecursiveIteratorIterator(
            $RDI,
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        unset($RDI);

        $FI = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterRestricted(
            $RII, 
            $targetDir,
            $FoldersController->restrictedFolder
        );
        $FI->notType = 'dir';
        $FI = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterNoThumbnails(
            $FI,
            $FileSystem,
            $thumbnailSizes
        );
        $FI = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilterNoOriginal(
            $FI,
            $FileSystem
        );
        unset($FileSystem, $FoldersController, $targetDir, $thumbnailSizes);

        $RII = new \LimitIterator($FI, $offset, $this->scanMaxFilesATime);
        unset($FI);
        $i = 0;

        foreach ($RII as $filename => $File) {
            $relatePath = str_replace(
                ['/', '\\', DIRECTORY_SEPARATOR],
                '/',
                str_replace(
                    PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName . DIRECTORY_SEPARATOR, 
                    '', 
                    $File->getPathname()
                )
            );

            $file_folder = ltrim(
                str_replace(
                    ['/', '\\', DIRECTORY_SEPARATOR],
                    '/',
                    str_replace(
                        PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName, 
                        '', 
                        $File->getPath()
                    )
                ),
                '/'
            );
            $output[] = [
                'file_folder' => $file_folder,
                'file_name' => $File->getFilename(),
                'realPath' => $File->getPathname(),
                'realPathHash' => sha1($File->getPathname()),
                'relatePath' => $relatePath,
                'url' => $Url->getPublicUrl() . '/' . 
                    $this->rootPublicFolderName . 
                    (!empty($file_folder) ? '/' . $file_folder : '') .
                    '/' . $File->getFilename(),
            ];

            unset($file_folder, $relatePath);

            $i++;
        }// endforeach;
        unset($File, $filename);

        unset($i, $RII, $Url);

        return $output;
    }// doScanFileSystem


    /**
     * Do scan unindexed files.
     * 
     * This method was called from `indexAction()`.
     * 
     * @return array Return array with keys `totalFiles`, `items`.
     */
    protected function doScanUnindexedFiles(): array
    {
        $files = $this->doScanFileSystem();
        $totalFiles = count($files);
        $items = [];

        $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container);
        $sql = 'SELECT * FROM `' . $FilesDb->tableName . '` AS `files`';

        $bindValues = [];
        if (!empty($files)) {
            $sql .= ' WHERE 1';
            $placeholders = [];
            $i = 1;
            foreach ($files as $fileItem) {
                $placeholders[] = '(`file_folder` = :file_folder' . $i . ' AND `file_name` = :file_name' . $i . ' AND `file_visibility` = 1)';
                $bindValues[':file_folder' . $i] = $fileItem['file_folder'];
                $bindValues[':file_name' . $i] = $fileItem['file_name'];
                $i++;
            }// endforeach;
            unset($fileItem, $i);
            $sql .= ' AND (';
            $sql .= implode(' OR ', $placeholders);
            $sql .= ')';
            unset($placeholders);
        }

        $Sth = $this->Db->PDO()->prepare($sql);
        foreach ($bindValues as $name => $value) {
            $Sth->bindValue($name, $value);
        }// endforeach;
        unset($name, $value);
        $Sth->execute();

        $result = $Sth->fetchAll();
        unset($FilesDb, $Sth);

        if (is_array($result)) {
            foreach ($files as $fileItem) {
                $found = false;
                foreach ($result as $row) {
                    if (
                        $fileItem['file_folder'] === $row->file_folder &&
                        $fileItem['file_name'] === $row->file_name
                    ) {
                        $found = true;
                        break;
                    }
                }// endforeach;
                unset($row);

                if (false === $found) {
                    $items[] = $fileItem;
                }
            }// endforeach;
            unset($fileItem);
        }
        unset($files, $result);

        return [
            'totalFiles' => (int) $totalFiles,
            'totalUnIndex' => (int) count($items),
            'items' => $items,
        ];
    }// doScanUnindexedFiles


    /**
     * Scan unindexed files page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['add']);

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
        $output['pageTitle'] = d__('rdbcmsa', 'Scan for unindexed files');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $output['scanMaxFilesATime'] = $this->scanMaxFilesATime;
        $output['urls'] = $this->getFilesUrlsMethod();

        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom HTTP accept, response content or AJAX.
            list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);
            if ($Csrf->validateToken($_GET[$csrfName], $_GET[$csrfValue])) {
                // if validate token passed.
                // scan via REST API.
                $output['scannedItems'] = $this->doScanUnindexedFiles();
            } else {
                // if unable to validate token.
                http_response_code(400);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
                $output = array_merge($output, $Csrf->createToken());
            }
            unset($csrfName, $csrfValue);

            if (isset($_SESSION['formResult'])) {
                $formResult = json_decode($_SESSION['formResult'], true);
                if (is_array($formResult)) {
                    $output['formResultStatus'] = strip_tags(key($formResult));
                    $output['formResultMessage'] = current($formResult);
                }
                unset($formResult, $_SESSION['formResult']);
            }
        } else {
            $output = array_merge($output, $Csrf->createToken());

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
                    'link' => $output['urls']['scanUnindexedUrl'],
                ],
            ];
            unset($urlBaseWithLang);
        }

        unset($Csrf);

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

            $Assets->addMultipleAssets(
                'css', 
                ['rdbcmsaScanUnindexedAction'], 
                $Assets->mergeAssetsData('css', $moduleAssetsData, $rdbAdminAssets)
            );
            $Assets->addMultipleAssets(
                'js', 
                ['rdbcmsaFilesScanUnindexed'], 
                $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets)
            );
            $Assets->addJsObject(
                'rdbcmsaFilesScanUnindexed',
                'RdbCMSAFilesScanUnindexedObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'offset' => 0,
                ],
                    $this->getFilesUrlsMethod()
                )
            );

            // include html functions file to use `renderBreadcrumbHtml()` function.
            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Files/scanUnindexed_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
