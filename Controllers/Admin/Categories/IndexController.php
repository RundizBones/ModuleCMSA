<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Categories;


/**
 * Categories controller.
 * 
 * @since 0.0.1
 */
class IndexController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\CategoriesTrait;


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\TranslationMatcher\Traits\TranslationMatcherTrait;


    /**
     * Get categories data.
     * 
     * @param array $configDb
     * @return array
     */
    protected function doGetCategories(array $configDb): array
    {
        $columns = $this->Input->get('columns', [], FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
        $order = $this->Input->get('order', [], FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
        $DataTablesJs = new \Rdb\Modules\RdbAdmin\Libraries\DataTablesJs();
        $sortOrders = $DataTablesJs->buildSortOrdersFromInput($columns, $order);
        unset($columns, $DataTablesJs, $order);

        $output = [];

        $CategoriesDb = new \Rdb\Modules\RdbCMSA\Models\CategoriesDb($this->Db->PDO(), $this->Container);
        $options = [];
        //$options['unlimited'] = true;
        $options['offset'] = $this->Input->get('start', 0, FILTER_SANITIZE_NUMBER_INT);
        $options['limit'] = $this->Input->get('length', $configDb['rdbadmin_AdminItemsPerPage'], FILTER_SANITIZE_NUMBER_INT);
        if (isset($_GET['search']['value']) && !empty(trim($_GET['search']['value']))) {
            $options['search'] = trim($_GET['search']['value']);
        }
        $options['where'] = [
            'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
            't_type' => $this->taxonomyType,
        ];
        $options['list_flatten'] = true;
        try {
            $result = $CategoriesDb->listRecursive($options);

            // do additional task(s) to the data that retrieved. ----------
            if ($this->Container->has('Config')) {
                /* @var $Config \Rdb\System\Config */
                $Config = $this->Container->get('Config');
                $Config->setModule('');
            } else {
                $Config = new \Rdb\System\Config();
            }
            $languages = $Config->get('languages', 'language', []);
            unset($Config);
            $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);

            if (isset($result['items']) && is_iterable($result['items']) && !empty($result['items'])) {
                // build object IDs to retrieve all related data at once. ------------
                $tids = [];
                foreach ($result['items'] as $row) {
                    $tids[] = $row->tid;
                }// endforeach;
                unset($row);
                // end build object IDs to retrieve all related data at once. -------

                // retrieve all related data at once. ----------------------------------
                $tmResults = $this->getLanguagesAndTranslationMatchedMultipleObjects($tids, $languages, $TranslationMatcherDb);
                unset($tids);
                // end retrieve all related data at once. -----------------------------

                foreach ($result['items'] as $row) {
                    if (array_key_exists(intval($row->tid), $tmResults)) {
                        $row->languages = $tmResults[intval($row->tid)];
                    } else {
                        $row->languages = [];
                    }
                }// endforeach;
                unset($row);
                unset($tmResults);
            }
            unset($languages, $TranslationMatcherDb);
            // end do additional task(s) to the data that retrieved. ------
        } catch (\Exception $e) {
            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container->get('Logger');
                $Logger->write('modules/cms/controllers/admin/categories/indexcontroller', 5, $e->getMessage());
                unset($Logger);
            }
            $output['containsError'] = true;
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = $e->getMessage();
            $result = [];
        }
        unset($CategoriesDb, $options, $sortOrders);

        $output['draw'] = $this->Input->get('draw', 1, FILTER_SANITIZE_NUMBER_INT);
        $output['recordsTotal'] = ($result['total'] ?? 0);
        $output['recordsFiltered'] = $output['recordsTotal'];
        $output['listItems'] = ($result['items'] ?? []);

        return $output;
    }// doGetCategories


    /**
     * Get a category data.
     * 
     * @param int $tid
     * @return string
     */
    public function doGetDataAction($tid): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentCategories', ['list', 'add', 'edit', 'delete']);

        $tid = (int) $tid;

        $this->Languages->getHelpers();

        // get a category data.
        $CategoriesDb = new \Rdb\Modules\RdbCMSA\Models\CategoriesDb($this->Db->PDO(), $this->Container);
        $where = [];
        $where['tid'] = $tid;
        $categoryRow = $CategoriesDb->get($where);
        unset($CategoriesDb, $where);

        $output = [];

        if (is_object($categoryRow) && !empty($categoryRow)) {
            $output['category'] = $categoryRow;
        } else {
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = d__('rdbcmsa', 'Not found selected category.');
            $output['category'] = null;
            http_response_code(404);
        }

        unset($roomRateRow);

        // display, response part ---------------------------------------------------------------------------------------------
        return $this->responseAcceptType($output);
    }// doGetDataAction


    /**
     * Categories listing (management) page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentCategories', ['list', 'add', 'edit', 'delete']);

        if (session_id() === '') {
            // don't set session cache because when it is in filtered category type page and click on add button, the error from get parent category will be occurs.
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Categories');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom HTTP accept, response content or AJAX.
            // get data via REST API.
            $output = array_merge($output, $this->doGetCategories($output['configDb']));

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

        $output['urls'] = $this->getCategoriesUrlMethods();
        $output['t_type'] = $this->taxonomyType;

        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        unset($ConfigDb);

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
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

            $FileBrowserSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FileBrowserSubController($this->Container);// required for tinymce file browser dialog

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage', 'rdbcmsaCategoriesIndexAction'], $Assets->mergeAssetsData('css', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addMultipleAssets('js', ['rdbcmsaCategoriesIndexAction'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaCategoriesIndexAction',
                'RdbCMSACategoriesIndexObject',
                array_merge([
                    'isInDataTablesPage' => true,
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
                    'baseUrl' => $Url->getAppBasedPath(true),
                    't_type' => $output['t_type'],
                    'txtConfirmDelete' => __('Are you sure to delete?'),
                    'txtPleaseSelectAction' => __('Please select an action.'),
                    'txtPleaseSelectAtLeastOne' => d__('rdbcmsa', 'Please select at least one category.'),
                    'txtPublished' => d__('rdbcmsa', 'Published'),
                    'txtUnpublished' => d__('rdbcmsa', 'Unpublished'),
                ], 
                    $this->getCategoriesUrlMethods(),
                    $FileBrowserSubController->fileBrowserGetJsObjects()// required for tinymce file browser dialog
                )
            );

            unset($FileBrowserSubController);

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Categories/index_v', $output);

            unset($Assets, $rdbAdminAssets);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
