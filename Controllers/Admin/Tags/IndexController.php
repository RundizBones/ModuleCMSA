<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Tags;


/**
 * Tag listing controller.
 * 
 * @since 0.0.1
 */
class IndexController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\TagsTrait;


    /**
     * Get an item.
     * 
     * @param string $tid The ID matched `tid` column in DB.
     * @return string
     */
    public function doGetItemAction(string $tid): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentTags', ['list', 'edit', 'delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];

        $TagsDb = new \Rdb\Modules\RdbCMSA\Models\TagsDb($this->Container);
        $resultRow = $TagsDb->get(['tid' => $tid]);
        unset($TagsDb);

        if (is_object($resultRow) && !empty($resultRow)) {
            $output['result'] = $resultRow;
        } else {
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = d__('rdbcmsa', 'Not found selected tag.');
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

        $TagsDb = new \Rdb\Modules\RdbCMSA\Models\TagsDb($this->Container);
        $options = [];
        $options['sortOrders'] = $sortOrders;
        $options['offset'] = $this->Input->get('start', 0, FILTER_SANITIZE_NUMBER_INT);
        $options['limit'] = $this->Input->get('length', $configDb['rdbadmin_AdminItemsPerPage'], FILTER_SANITIZE_NUMBER_INT);
        if (isset($_GET['search']['value']) && !empty(trim($_GET['search']['value']))) {
            $options['search'] = trim($_GET['search']['value']);
        }
        $options['where'] = [
            'taxonomy_term_data.language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
            't_type' => $this->Input->get('filter-t_type', $this->tagTaxonomyType, FILTER_SANITIZE_STRING),
        ];
        $result = $TagsDb->listItems($options);
        unset($options, $TagsDb);

        $output['draw'] = $this->Input->get('draw', 1, FILTER_SANITIZE_NUMBER_INT);
        $output['recordsTotal'] = ($result['total'] ?? 0);
        $output['recordsFiltered'] = $output['recordsTotal'];
        $output['listItems'] = ($result['items'] ?? []);

        return $output;
    }// doGetItems


    /**
     * Listing page action.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentTags', ['list', 'add', 'edit', 'delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Tags');
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

        $output['urls'] = $this->getTagsUrlsMethod();
        $output['t_type'] = $this->tagTaxonomyType;
        if (isset($_GET['filter-t_type'])) {
            $output['t_type'] = trim($this->Input->get('filter-t_type', $this->tagTaxonomyType, FILTER_SANITIZE_STRING));
            $output['urls']['addTagUrl'] .= '?t_type=' . $output['t_type'];
        }

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom HTTP accept, response content or AJAX.
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
            $Assets->addMultipleAssets('js', ['rdbcmsaTagsIndexAction'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaTagsIndexAction',
                'RdbCMSATagsIndexObject',
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
                    'txtPleaseSelectAtLeastOne' => d__('rdbcmsa', 'Please select at least one item.'),
                ], $this->getTagsUrlsMethod())
            );

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Tags/index_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
