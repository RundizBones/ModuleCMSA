<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Categories;


/**
 * Add category controller.
 * 
 * @since 0.0.1
 */
class AddController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\CategoriesTrait;


    /**
     * Do add a category via REST API.
     * 
     * @return string
     */
    public function doAddAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentCategories', ['add']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getCategoriesUrlMethods();

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            // prepare data for checking.
            $data = [];
            $dataUrlAliases = [];
            $data['parent_id'] = trim($this->Input->post('parent_id', 0, FILTER_SANITIZE_NUMBER_INT));
            $data['t_type'] = trim($this->Input->post('t_type', $this->taxonomyType, FILTER_SANITIZE_STRING));
            $data['t_name'] = trim($this->Input->post('t_name', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $data['t_description'] = trim($this->Input->post('t_description', null));
            $data['t_status'] = trim($this->Input->post('t_status', 0, FILTER_SANITIZE_NUMBER_INT));
            // set null if empty.
            $InputUtils = new \Rdb\Modules\RdbCMSA\Libraries\InputUtils();
            $data = $InputUtils->setEmptyScalarToNull($data);
            unset($InputUtils);
            $data['language'] = ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th');
            if (isset($_POST['alias_url']) && !empty(trim($_POST['alias_url']))) {
                $dataUrlAliases['alias_content_type'] = $data['t_type'];
                $dataUrlAliases['language'] = $data['language'];
                $dataUrlAliases['alias_url'] = $this->Input->post('alias_url', null);
            }

            $CategoriesDb = new \Rdb\Modules\RdbCMSA\Models\CategoriesDb($this->Db->PDO(), $this->Container);
            $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            if (empty($data['t_name'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Please enter the name.');
                http_response_code(400);
                $formValidated = false;
            } else {
                $formValidated = true;
            }

            if ($formValidated === true && !empty($dataUrlAliases)) {
                // check for duplicated URL from URL aliases.
                $isDuplicated = $UrlAliasesDb->isDuplicatedUrl($dataUrlAliases['alias_url'], $dataUrlAliases['language']);
                if ($isDuplicated !== false) {
                    // if URL is already exists.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'The URL is already exists.');
                    $output['duplicatedUrlNotFound'] = false;
                    $output['duplicatedIn'] = 'url_aliases';
                    http_response_code(400);
                    $formValidated = false;
                } else {
                    $output['duplicatedUrlNotFound'] = true;
                }
                unset($isDuplicated);
            }
            // end validate the form. --------------------------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                try {
                    $tid = $CategoriesDb->add($data);

                    if ($tid !== false && $tid > 0 && !empty($dataUrlAliases)) {
                        $dataUrlAliases['alias_content_id'] = $tid;
                        $UrlAliasesDb->add($dataUrlAliases);
                    }
                    unset($dataUrlAliases);
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage();
                    $tid = false;
                    $output['errcatch'] = true;
                }

                if ($tid !== false && $tid > '0') {
                    $output['tid'] = $tid;
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Added successfully.');
                    http_response_code(201);

                    $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                    unset($output['formResultMessage'], $output['formResultStatus']);
                    $output['redirectBack'] = $output['urls']['getCategoriesUrl'] . '?filter-t_type=' . $data['t_type'];
                } else {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to add new category.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }
                unset($tid);
            }

            unset($CategoriesDb, $data, $dataUrlAliases, $formValidated, $UrlAliasesDb);
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
    }// doAddAction


    /**
     * Add a category page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentCategories', ['add']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Add a category');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $output['urls'] = $this->getCategoriesUrlMethods();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        $output['baseUrl'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true);
        $output['t_type'] = trim($this->Input->get('t_type', $this->taxonomyType, FILTER_SANITIZE_STRING));

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
                'item' => d__('rdbcmsa', 'Categories'),
                'link' => $output['urls']['getCategoriesUrl'],
            ],
            [
                'item' => $output['pageTitle'],
                'link' => $output['urls']['addCategoryUrl'],
            ],
        ];
        unset($urlBaseWithLang);

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

            $FileBrowserSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\FileBrowserSubController($this->Container);// required for tinymce file browser dialog

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage'], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbcmsaCategoriesAddAction', 'rdbaHistoryState'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaCategoriesAddAction',
                'RdbCMSACategoriesIndexObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
                    't_type' => $output['t_type'],
                ], 
                    $this->getCategoriesUrlMethods(),
                    $FileBrowserSubController->fileBrowserGetJsObjects()// required for tinymce file browser dialog
                )
            );

            unset($FileBrowserSubController);

            // include html functions file to use `renderBreadcrumbHtml()` function.
            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Categories/add_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
