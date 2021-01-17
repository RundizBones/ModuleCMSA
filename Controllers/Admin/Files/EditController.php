<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Files;


/**
 * Edit file controller.
 */
class EditController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\FilesTrait;


    /**
     * Update a file data.
     * 
     * @param string $file_id
     * @return string
     */
    public function doUpdateAction(string $file_id): string
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

        // make patch data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validate csrf passed.
            $file_id = (int) $file_id;
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            // prepare data for checking.
            $data = [];
            $data['file_media_name'] = trim($this->Input->patch('file_media_name', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $data['file_media_description'] = trim($this->Input->patch('file_media_description'));
            $data['file_media_keywords'] = trim($this->Input->patch('file_media_keywords', null, FILTER_SANITIZE_STRING));
            $data['file_status'] = $this->Input->patch('file_status', 0, FILTER_SANITIZE_NUMBER_INT);
            // set null if empty.
            $InputUtils = new \Rdb\Modules\RdbCMSA\Libraries\InputUtils();
            $data = $InputUtils->setEmptyScalarToNull($data);
            unset($InputUtils);
            if (is_null($data['file_status'])) {
                $data['file_status'] = 0;
            }

            $FilesDb = new \Rdb\Modules\RdbCMSA\Models\FilesDb($this->Container);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            $resultRow = $FilesDb->get(['file_id' => $file_id]);
            if (is_object($resultRow) && !empty($resultRow)) {
                $formValidated = true;
            } else {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Not found selected item.');
                http_response_code(404);
                $formValidated = false;
            }
            // end validate the form. --------------------------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                try {
                    $saveResult = $FilesDb->update($data, ['file_id' => $file_id]);
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage();
                    $output['errcatch'] = true;
                    $saveResult = false;
                }

                if ($saveResult === true) {
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Updated successfully.');
                    http_response_code(200);

                    $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                    unset($output['formResultMessage'], $output['formResultStatus']);
                    $output['redirectBack'] = $output['urls']['getFilesUrl'];
                } else {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to update.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }
                unset($saveResult);
            }// endif; $formValidated

            unset($data, $FilesDb);
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
    }// doUpdateAction


    /**
     * Edit page action.
     * 
     * @param string $file_id The file ID.
     * @return string
     */
    public function indexAction(string $file_id): string
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
        $output['pageTitle'] = d__('rdbcmsa', 'Edit file data');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $output['file_id'] = (int) $file_id;
        $output['urls'] = $this->getFilesUrlsMethod();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        $output['baseUrl'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true);

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
                'link' => $output['urls']['editFileUrlBase'] . '/' . $file_id,
            ],
        ];
        unset($urlBaseWithLang);

        $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\FilesSubController();

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

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage', 'rdbcmsaFilesEditAction'], $Assets->mergeAssetsData('css', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addMultipleAssets('js', ['rdbcmsaFilesEditAction', 'rdbaHistoryState'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaFilesEditAction',
                'RdbCMSAFilesCommonObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'imageExtensions' => $FilesSubController->imageExtensions,
                    'rootPublicUrl' => $Url->getPublicUrl(),
                    'rootPublicFolderName' => $this->rootPublicFolderName,
                    'txtImageDimension' => d__('rdbcmsa', 'Image dimension'),
                    'txtVideoDimension' => d__('rdbcmsa', 'Video dimension'),
                ], $this->getFilesUrlsMethod())
            );

            // include html functions file to use `renderBreadcrumbHtml()` function.
            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Files/edit_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $FilesSubController, $rdbAdminAssets);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
