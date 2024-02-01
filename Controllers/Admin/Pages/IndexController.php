<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Pages;


/**
 * Pages listing controller.
 * 
 * @since 0.0.1
 */
class IndexController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\PagesTrait;


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Categories\Traits\CategoriesTrait;


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Tags\Traits\TagsTrait;


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\TranslationMatcher\Traits\TranslationMatcherTrait;


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Traits\UsersTrait;


    /**
     * @var \Rdb\Modules\RdbCMSA\Models\PostsDb $PostsDb 
     */
    protected $PostsDb;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->PostsDb = new \Rdb\Modules\RdbCMSA\Models\PostsDb($Container);
        $this->PostsDb->postType = $this->postType;
        $this->PostsDb->categoryType = $this->categoryType;
        $this->PostsDb->tagType = $this->tagType;
    }// __construct


    /**
     * Get filters value.
     * 
     * @return string
     */
    public function doGetFiltersAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPages', ['list', 'add', 'edit', 'delete']);

        $PostsSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\PostsSubController($this->Container);
        $PostsSubController->PostsDb = $this->PostsDb;
        $PostsSubController->categoryType = $this->categoryType;
        $PostsSubController->postType = $this->postType;
        $PostsSubController->tagType = $this->tagType;

        $output = [];

        // get statuses
        $output['allPostStatuses'] = $PostsSubController->getStatusesForSelectbox();
        // get users who wrote the posts on specified post type
        $output['allAuthors'] = $PostsSubController->getAuthorsForSelectbox();

        unset($PostsSubController);

        // display, response part ---------------------------------------------------------------------------------------------
        return $this->responseAcceptType($output);
    }// doGetFiltersAction


    /**
     * Get an item for admin usage.
     * 
     * @param string $post_id The ID matched `post_id` column in DB.
     * @return string
     */
    public function doGetItemAction(string $post_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPages', ['list', 'add', 'edit', 'delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];

        $resultRow = $this->PostsDb->get(['posts.post_id' => $post_id], ['countRevision' => true]);

        if (is_object($resultRow) && !empty($resultRow)) {
            $output['result'] = $resultRow;
        } else {
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = d__('rdbcmsa', 'Not found selected page.');
            $output['result'] = null;
            http_response_code(404);
        }

        unset($resultRow);

        // display, response part ---------------------------------------------------------------------------------------------
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

        $options = [];
        $options['sortOrders'] = $sortOrders;
        $options['offset'] = $this->Input->get('start', 0, FILTER_SANITIZE_NUMBER_INT);
        $options['limit'] = $this->Input->get('length', $configDb['rdbadmin_AdminItemsPerPage'], FILTER_SANITIZE_NUMBER_INT);
        if (isset($_GET['search']['value']) && !empty(trim($_GET['search']['value']))) {
            $options['search'] = trim($_GET['search']['value']);
        }
        $options['where'] = [
            'posts.language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
            'post_type' => $this->postType,
        ];
        if (trim($this->Input->get('filter-post_status')) !== '') {
            $options['where']['post_status'] = (int) $this->Input->get('filter-post_status');
        } else {
            $options['where']['post_status'] = '< 5';
        }
        if (trim($this->Input->get('filter-tid')) !== '') {
            $options['tidsIn'] = [(int) $this->Input->get('filter-tid')];
        }
        if (!empty(trim($this->Input->get('filter-user_id')))) {
            $options['where']['posts.user_id'] = (int) $this->Input->get('filter-user_id');
        }
        if (trim($this->Input->get('filter-tag-tid')) !== '') {
            if (!isset($options['tidsIn'])) {
                $options['tidsIn'] = [];
            }
            $options['tidsIn'] = array_merge($options['tidsIn'], [(int) $this->Input->get('filter-tag-tid')]);
        }
        $result = $this->PostsDb->listItems($options);
        unset($options);

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

        if (isset($result['items']) && is_iterable($result['items'])) {
            // build object IDs to retrieve all related data at once. ------------
            $postIds = [];
            foreach ($result['items'] as $row) {
                $postIds[] = $row->post_id;
            }// endforeach;
            unset($row);
            // end build object IDs to retrieve all related data at once. -------

            // retrieve all related data at once. ----------------------------------
            $tmResults = $this->getLanguagesAndTranslationMatchedMultipleObjects($postIds, $languages, $TranslationMatcherDb, 'posts');
            unset($postIds);
            // end retrieve all related data at once. -----------------------------

            foreach ($result['items'] as $row) {
                if (array_key_exists(intval($row->post_id), $tmResults)) {
                    $row->languages = $tmResults[intval($row->post_id)];
                } else {
                    $row->languages = [];
                }
            }// endforeach;
            unset($row);
            unset($tmResults);
        }
        unset($languages, $TranslationMatcherDb);
        // end do additional task(s) to the data that retrieved. ------

        $output['draw'] = $this->Input->get('draw', 1, FILTER_SANITIZE_NUMBER_INT);
        $output['recordsTotal'] = ($result['total'] ?? 0);
        $output['recordsFiltered'] = $output['recordsTotal'];
        $output['listItems'] = ($result['items'] ?? []);

        return $output;
    }// doGetItems


    /**
     * The page listing action.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPages', ['list', 'add', 'edit', 'delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Pages');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

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

        $output['urls'] = $this->getPostsUrlsMethod();

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
                'item' => $output['pageTitle'],
                'link' => $output['urls']['getPostsUrl'],
            ],
        ];
        unset($urlBaseWithLang);

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

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage'], $Assets->mergeAssetsData('css', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addMultipleAssets('js', ['rdbcmsaPagesIndexAction'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaPagesIndexAction',
                'RdbCMSAPostsIndexObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
                    'baseUrl' => $Url->getAppBasedPath(true),
                    'postType' => $this->postType,
                    'categoryType' => $this->categoryType,
                    'tagType' => $this->tagType,
                    'txtConfirmDelete' => __('Are you sure to delete?'),
                    'txtDateAdd' => d__('rdbcmsa', 'Add: %s'),
                    'txtDateUpdate' => d__('rdbcmsa', 'Update: %s'),
                    'txtDeletedSuccessfully' => d__('rdbcmsa', 'Items deleted successfully.'),
                    'txtDatePublish' => d__('rdbcmsa', 'Publish: %s'),
                    'txtPleaseSelectAction' => __('Please select an action.'),
                    'txtPleaseSelectAtLeastOne' => d__('rdbcmsa', 'Please select at least one item.'),
                ], 
                    $this->getPostsUrlsMethod(), 
                    $this->getUserUrlsMethods(),
                    $this->getCategoriesUrlMethods(),
                    $this->getTagsUrlsMethod()
                )
            );

            // include html functions file to use `renderBreadcrumbHtml()` function.
            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Pages/index_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
