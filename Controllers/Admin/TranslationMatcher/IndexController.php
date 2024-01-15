<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\TranslationMatcher;


/**
 * Translation matcher.
 * 
 * @since 0.0.2
 */
class IndexController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\TranslationMatcherTrait;


    /**
     * Get a translation matcher data.
     * 
     * @param string $tm_id
     * @return string
     */
    public function doGetDataAction(string $tm_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSATranslationMatcher', ['list', 'match']);

        $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);

        $tm_id = (int) $tm_id;
        $output = [];

        $where = [
            'tm_id' => $tm_id,
        ];
        $options = [
            'getRelatedData' => true,
        ];
        $output['result'] = $TranslationMatcherDb->get($where, $options);
        unset($options, $where);

        unset($TranslationMatcherDb);

        // display, response part ---------------------------------------------------------------------------------------------
        return $this->responseAcceptType($output);
    }// doGetDataAction


    /**
     * Get translation matcher items.
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
        $tmTable = $this->Input->get('filter-tm_table', 'posts');
        unset($columns, $DataTablesJs, $order);

        $output = [];

        $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);
        $options['sortOrders'] = $sortOrders;
        $options['offset'] = $this->Input->get('start', 0, FILTER_SANITIZE_NUMBER_INT);
        $options['limit'] = $this->Input->get('length', $configDb['rdbadmin_AdminItemsPerPage'], FILTER_SANITIZE_NUMBER_INT);
        if (isset($_GET['search']['value']) && !empty(trim($_GET['search']['value']))) {
            $options['search'] = trim($_GET['search']['value']);
        }
        if (!empty($tmTable)) {
            $options['where'] = [
                'tm_table' => $tmTable,
            ];
        }
        $options['getRelatedData'] = true;
        $result = $TranslationMatcherDb->listItems($options);
        unset($options, $sortOrders, $tmTable, $TranslationMatcherDb);

        $output['draw'] = $this->Input->get('draw', 1, FILTER_SANITIZE_NUMBER_INT);
        $output['recordsTotal'] = ($result['total'] ?? 0);
        $output['recordsFiltered'] = $output['recordsTotal'];
        $output['listItems'] = ($result['items'] ?? []);

        return $output;
    }// doGetItems


    /**
     * Search data on editing.
     * 
     * @return string
     */
    public function doSearchEditingAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSATranslationMatcher', ['list', 'match']);

        $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);

        $output = [];

        $tmId = trim($this->Input->get('tm_id'));
        $tmTable = trim($this->Input->get('tm_table', 'posts'));
        $dataInput = trim($this->Input->get('prog_data-input'));
        $dataLanguage = trim($this->Input->get('prog_language-id'));
        $editingMode = trim($this->Input->get('editingMode'));

        if (!empty($dataInput) && !empty($dataLanguage)) {
            if (strtolower($tmTable) === 'posts') {
                // if searching on posts table.
                $sql = 'SELECT `posts`.*, 
                    `posts`.`post_id` AS `data_id`, 
                    `posts`.`post_type` AS `data_type`, 
                    `posts`.`post_name` AS `data_name` 
                    FROM `' . $this->Db->tableName('posts') . '` AS `posts` WHERE 
                    (
                        `post_id` = :dataInput
                        OR `post_name` LIKE :searchDataInput
                    ) AND (`language` = :dataLanguage)
                    LIMIT 0, 10';
                $Sth = $this->Db->PDO()->prepare($sql);
                unset($sql);
            } elseif (strtolower($tmTable) === 'taxonomy_term_data') {
                // if searching on taxonomy_term_data table.
                $sql = 'SELECT `taxonomy_term_data`.*, 
                    `taxonomy_term_data`.`tid` AS `data_id`, 
                    `taxonomy_term_data`.`t_type` AS `data_type`, 
                    `taxonomy_term_data`.`t_name` AS `data_name` 
                    FROM `' . $this->Db->tableName('taxonomy_term_data') . '` AS `taxonomy_term_data` WHERE 
                    (
                        `tid` = :dataInput
                        OR `t_name` LIKE :searchDataInput
                        -- OR `t_description` LIKE :searchDataInput
                    ) AND (`language` = :dataLanguage)
                    LIMIT 0, 10';
                $Sth = $this->Db->PDO()->prepare($sql);
                unset($sql);
            }
        }// endif; not empty input data & language.

        if (isset($Sth)) {
            $Sth->bindValue(':dataInput', $dataInput);
            $Sth->bindValue(':searchDataInput', '%' . $dataInput . '%');
            $Sth->bindValue(':dataLanguage', $dataLanguage);
            $Sth->execute();
            $result = $Sth->fetchAll();
            $Sth->closeCursor();
            unset($Sth);
        }

        unset($dataInput, $dataLanguage);

        if (isset($result)) {
            if (strtolower($editingMode) === 'add') {
                // if add mode.
                // remove duplicated result in db. -------------------------
                // build data ids to look in db.
                $findDataIds = [];
                foreach ($result as $row) {
                    $findDataIds[] = (int) $row->data_id;
                }// endforeach;
                unset($row);
                $TranslationMatcherDb->isIdsExists($findDataIds, $tmTable);
                $tmResult = [
                    'items' => $TranslationMatcherDb->isIdsExistsResult
                ];
                unset($findDataIds);

                $this->removeExistsDataFromResult($result, $tmResult);
                unset($tmResult);
                // end remove duplicated result in db. ------------------------
            } elseif (strtolower($editingMode) === 'edit' && !empty($tmId)) {
                // if edit mode.
                // remove duplicated result in db except selected tm_id. ----
                // build data ids to look in db.
                $options['findDataIds'] = [];
                foreach ($result as $row) {
                    $options['findDataIds'][] = (int) $row->data_id;
                }// endforeach;
                unset($row);
                $options['where'] = [
                    'tm_table' => $tmTable,
                    'tm_id' => '!= ' . $tmId,// except this tm_id.
                ];
                $options['unlimited'] = true;
                $tmResult = $TranslationMatcherDb->listItems($options);// look in db.
                unset($options);

                $this->removeExistsDataFromResult($result, $tmResult);
                unset($tmResult);
                // end remove duplicated result in db except selected tm_id. --
            }

            $output['total'] = count($result);
            $output['items'] = array_values($result);// use array_values to re-index array after some items maybe removed.
            unset($result);
        } else {
            $output['total'] = 0;
            $output['items'] = [];
        }

        unset($editingMode, $tmId, $tmTable, $TranslationMatcherDb);

        // display, response part ---------------------------------------------------------------------------------------------
        return $this->responseAcceptType($output);
    }// doSearchEditingAction


    /**
     * Translation matches listing page action.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSATranslationMatcher', ['list', 'match']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Translation matcher');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        if ($this->Container->has('Config')) {
            /* @var $Config \Rdb\System\Config */
            $Config = $this->Container->get('Config');
            $Config->setModule('');
        } else {
            $Config = new \Rdb\System\Config();
        }
        $output['Config'] = $Config;
        $output['languages'] = $Config->get('languages', 'language', []);

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

        $output['urls'] = $this->getTMUrlsMethod();

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

            $Assets->addMultipleAssets(
                'css', 
                ['rdta', 'datatables', 'rdbaCommonListDataPage'], 
                $Assets->mergeAssetsData('css', $moduleAssetsData, $rdbAdminAssets)
            );
            $Assets->addMultipleAssets(
                'js', 
                ['rdbcmsaTranslationMatcherIndexAction'], 
                $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets)
            );
            $Assets->addJsObject(
                'rdbcmsaTranslationMatcherIndexAction',
                'RdbCMSATranslationMatcherIndexObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'editingMode' => null,
                    'languages' => $output['languages'],
                    'txtAddNew' => __('Add'),
                    'txtConfirmDelete' => __('Are you sure to delete?'),
                    'txtDeletedSuccessfully' => d__('rdbcmsa', 'Items deleted successfully.'),
                    'txtEdit' => __('Edit'),
                    'txtPleaseSelectAction' => __('Please select an action.'),
                    'txtPleaseSelectAtLeastOne' => d__('rdbcmsa', 'Please select at least one item.'),
                ], 
                    $this->getTMUrlsMethod()
                )
            );

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/TranslationMatcher/index_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


    /**
     * Remove exists data from `$result` if found matched exists in `$tmResult`.
     * 
     * This method was called from `doSearchEditingAction()`.
     * 
     * @since 0.0.14
     * @param array $result The raw data result to be remove if exists in `$tmResult`. This variable will be alter.
     * @param array $tmResult The searched translation matcher result.
     */
    protected function removeExistsDataFromResult(array &$result, array $tmResult)
    {
        if (isset($tmResult['items']) && is_array($tmResult['items'])) {
            foreach ($tmResult['items'] as $eachTm) {
                $jsonMatches = json_decode($eachTm->matches);
                foreach ($result as $resultIndex => $row) {
                    foreach ($jsonMatches as $languageId => $data_id) {
                        if (!empty($data_id) && $data_id == $row->data_id) {
                            // if found matched exists in db.
                            // removed data that exists in db.
                            unset($result[$resultIndex]);
                        }
                    }// endforeach;
                    unset($data_id, $languageId);
                }// endforeach; $result
                unset($jsonMatches, $resultIndex, $row);
            }// endforeach;
            unset($eachTm);
        }
    }// removeExistsDataFromResult


}
