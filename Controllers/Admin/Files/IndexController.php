<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Files;


/**
 * Files listing controller.
 * 
 * @since 0.0.1
 */
class IndexController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Traits\UsersTrait;


    use Traits\FilesTrait;


    /**
     * Get an item.
     * 
     * @param string $file_id The item ID.
     * @return string
     */
    public function doGetItemAction(string $file_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['list', 'edit', 'delete', 'move']);

        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $file_id = (int) $file_id;
        $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);
        $resultRow = $FilesDb->get(['file_id' => $file_id]);
        unset($FilesDb);

        if (is_object($resultRow) && !empty($resultRow)) {
            $output['result'] = $resultRow;
        } else {
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = d__('rdbcmsa', 'Not found selected item.');
            $output['result'] = null;
            http_response_code(404);
        }

        unset($resultRow);

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Url);
        return $this->responseAcceptType($output);
    }// doGetItemAction


    /**
     * Get multiple items.
     * 
     * This method was called from `indexAction()` method.
     * 
     * @param array $configDb
     * @return array
     */
    protected function doGetItems(array $configDb): array
    {
        $columns = $this->Input->get('columns', [], FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
        $order = $this->Input->get('order', [], FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
        $DataTablesJs = new \Rdb\Modules\RdbAdmin\Libraries\DataTablesJs();
        $sortOrders = $DataTablesJs->buildSortOrdersFromInput($columns, $order);
        unset($columns, $DataTablesJs, $order);

        $output = [];

        $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);
        $options = [];
        $options['sortOrders'] = $sortOrders;
        $options['offset'] = $this->Input->get('start', 0, FILTER_SANITIZE_NUMBER_INT);
        $options['limit'] = $this->Input->get('length', $configDb['rdbadmin_AdminItemsPerPage'], FILTER_SANITIZE_NUMBER_INT);
        if (isset($_GET['search']['value']) && !empty(trim($_GET['search']['value']))) {
            $options['search'] = trim($_GET['search']['value']);
        }
        $options['where'] = [
            'file_visibility' => 1,
            'file_folder' => strip_tags($this->Input->get('filter-file_folder')),
        ];
        $filterFileStatus = trim($this->Input->get('filter-file_status', '', FILTER_SANITIZE_NUMBER_INT));
        if (is_numeric($filterFileStatus)) {
            $options['where']['file_status'] = $filterFileStatus;
        }
        unset($filterFileStatus);
        $filterMime = strip_tags(trim($this->Input->get('filter-mimetype')));
        if (!empty($filterMime)) {
            $options['filterMime'] = $filterMime;
        }
        unset($filterMime);
        $result = $FilesDb->listItems($options);
        unset($FilesDb, $options);

        $output['draw'] = $this->Input->get('draw', 1, FILTER_SANITIZE_NUMBER_INT);
        $output['recordsTotal'] = ($result['total'] ?? 0);
        $output['recordsFiltered'] = $output['recordsTotal'];
        $output['listItems'] = ($result['items'] ?? []);

        return $output;
    }// doGetItems


    /**
     * Download a file.
     * 
     * @param string $file_id
     * @return string
     */
    public function downloadItemAction(string $file_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['list', 'add', 'edit', 'delete', 'move']);

        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $file_id = (int) $file_id;
        $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container, $this->rootPublicFolderName);
        // this is downloads for admin, do not check `file_status`.
        $resultRow = $FilesDb->get(['file_id' => $file_id], ['getFileFullPath' => true]);
        unset($FilesDb);

        if (is_object($resultRow) && !empty($resultRow)) {
            // if found.
            if (property_exists($resultRow, 'fileFullPath') && is_file($resultRow->fileFullPath)) {
                // if file exists.
                if (ob_get_level()) {
                    ob_end_clean();
                }

                // send header to force download.
                // @link https://www.php.net/manual/en/function.readfile.php Original source code.
                // @link https://stackoverflow.com/a/32092523/128761 Reference for use file info to detect mime type.
                // @link https://stackoverflow.com/a/1667651/128761 Reference for additional headers.
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                header('Content-Type: '.finfo_file($finfo, $resultRow->fileFullPath));
                $finfo = finfo_open(FILEINFO_MIME_ENCODING);
                header('Content-Transfer-Encoding: '.finfo_file($finfo, $resultRow->fileFullPath));
                header('Content-disposition: attachment; filename="' . $resultRow->file_original_name . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($resultRow->fileFullPath));
                readfile($resultRow->fileFullPath);
                exit();// required.
            } else {
                // if file not exists.
                $fileFound = false;
                if ($this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $Logger->write('modules/cms/controllers/admin/files/indexcontroller', 2, 'The file was not found.', ['resultRow' => $resultRow]);
                    unset($Logger);
                }
            }
        } else {
            $fileFound = false;
        }
        unset($resultRow);

        if (!isset($fileFound) || $fileFound !== true) {
            // if file was not found.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = d__('rdbcmsa', 'The selected file was not found.');
            http_response_code(404);
        }

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Url);
        return $this->responseAcceptType($output);
    }// downloadItemAction


    /**
     * Listing page action.
     * 
     * @return string
     */
    public function indexAction(): string
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
        $output['pageTitle'] = d__('rdbcmsa', 'Files');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();
        $output['rootPublicFolderName'] = $this->rootPublicFolderName;

        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom HTTP accept, response content or AJAX.
            // get data via REST API.
            $output = array_merge($output, $this->doGetItems($output['configDb']));

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
        }

        unset($Csrf);

        $output['urls'] = $this->getFilesUrlsMethod();
        $output['urls'] = array_merge($output['urls'], $this->getUserUrlsMethods());

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

            $Assets->addMultipleAssets('css', ['rdbcmsaFilesIndexAction', 'datatables', 'rdbaCommonListDataPage'], $Assets->mergeAssetsData('css', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addMultipleAssets('js', ['rdbcmsaFilesIndexActionFolders', 'rdbcmsaFilesIndexActionFiles'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaFilesCommonActions',
                'RdbCMSAFilesCommonObject',
                array_merge([
                    'isInDataTablesPage' => true,
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'audioExtensions' => $FilesSubController->audioExtensions,
                    'imageExtensions' => $FilesSubController->imageExtensions,
                    'videoExtensions' => $FilesSubController->videoExtensions,
                    'rootPublicUrl' => $Url->getPublicUrl(),
                    'rootPublicFolderName' => $this->rootPublicFolderName,
                    'txtAllFilesInWillBeDeleted' => d__('rdbcmsa', 'All files in this folder will be deleted.'),
                    'txtConfirmDelete' => __('Are you sure to delete?'),
                    'txtConfirmDeleteFolder' => d__('rdbcmsa', 'Are you sure to delete selected folder?'),
                    'txtDateAdd' => d__('rdbcmsa', 'Add: %s'),
                    'txtDateUpdate' => d__('rdbcmsa', 'Update: %s'),
                    'txtDeleteSuccessfully' => __('Deleted successfully.'),
                    'txtImageDimension' => d__('rdbcmsa', 'Image dimension'),
                    'txtPleaseSelectAction' => __('Please select an action.'),
                    'txtPleaseSelectAtLeastOne' => d__('rdbcmsa', 'Please select at least one item.'),
                    'txtPublished' => d__('rdbcmsa', 'Published'),
                    'txtUnpublished' => d__('rdbcmsa', 'Unpublished'),
                    'txtUploading' => __('Uploading'),
                    'txtVideoDimension' => d__('rdbcmsa', 'Video dimension'),
                ], 
                    $this->getFilesUrlsMethod(),
                    $this->getUserUrlsMethods()
                )
            );

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Files/index_v', $output);

            unset($Assets, $FilesSubController, $rdbAdminAssets, $Url);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
