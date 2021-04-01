<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers;


/**
 * Sub controller of taxonomy term data to do common jobs between different taxonomy types.
 * 
 * @since 0.0.5
 */
class TaxonomyTermDataSubController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    /**
     * @var \Rdb\Modules\RdbCMSA\Models\TaxonomyTermDataDb
     */
    public $TaxonomyTermDataDb;


    /**
     * @var string
     */
    public $taxonomyType;


    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        // bind text domain in case it is being use by other modules.
        $this->Languages->bindTextDomain(
            'rdbcmsa', 
            MODULE_PATH . DIRECTORY_SEPARATOR . 'RdbCMSA' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );
    }// __construct


    /**
     * Delete selected categories and related tables.
     * 
     * @param array $listSelectedCategories The selected categories that retrieved from `CategoriesDb->listRecursive()` or `validateCategoryActions()` method.
     *          The value must be array that is ready to start looping (basically it must be in `['items']` array).
     * @return array Return associative array with keys:<br>
     *          `deleteSuccess` (bool) Delete result. `true` if success, `false` if failure.<br>
     *          If contain error:<br>
     *              `errorMessage`(string) The thrown exception error message with trace as string.<br>
     *              `errcatch`(bool) This will be set to `true` if exception was thrown and catched.<br>
     */
    public function deleteCategories(array $listSelectedCategories): array
    {
        $CategoriesDb = new \Rdb\Modules\RdbCMSA\Models\CategoriesDb($this->Db->PDO(), $this->Container);
        $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);
        $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);

        if ($this->Container->has('Logger')) {
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $Logger = $this->Container->get('Logger');
        }

        $output = [];
        $output['deleteSuccess'] = false;
        $tidsArray = [];

        try {
            foreach ($listSelectedCategories as $row) {
                $deleteResult = $CategoriesDb->deleteACategory($row->tid, $this->taxonomyType);

                if ($deleteResult === true) {
                    $tidsArray[] = (int) $row->tid;
                }

                unset($deleteResult);
            }// endforeach;
            unset($row);

            if (isset($tidsArray) && !empty($tidsArray)) {
                // delete on url aliases table.
                $deleteUrlAlias = $UrlAliasesDb->deleteMultiple($this->taxonomyType, $tidsArray);
                if ($deleteUrlAlias !== true) {
                    if (isset($Logger)) {
                        $Logger->write('modules/cms/controllers/admin/subcontrollers/taxonomytermdatasubcontroller', 2, 'The URL alias for taxonomy ids {tids} haven\'t been delete.', ['tids' => $tidsArray]);
                    }
                }
                unset($deleteUrlAlias);

                // delete on translation matcher table.
                $deleteTM = $TranslationMatcherDb->deleteIfAllEmpty('taxonomy_term_data', $tidsArray);
                if ($deleteTM === false) {
                    if (isset($Logger)) {
                        $Logger->write('modules/cms/controllers/admin/subcontrollers/taxonomytermdatasubcontroller', 2, 'The translation matchers for taxonomy ids {tids} haven\'t been delete.', ['tids' => $tidsArray]);
                    }
                }
                unset($deleteTM);
            }

            $output['deleteSuccess'] = true;
        } catch (\Exception $ex) {
            $output['errorMessage'] = $ex->getMessage();
            $output['errcatch'] = true;
            $output['deleteSuccess'] = false;
        }// end try.

        unset($tidsArray);

        unset($CategoriesDb, $Logger, $TranslationMatcherDb, $UrlAliasesDb);
        return $output;
    }// deleteCategories


    /**
     * Delete selected tags and related tables.
     * 
     * @param array $listSelectedCategories The selected categories that retrieved from `CategoriesDb->listRecursive()` or `validateCategoryActions()` method.
     *          The value must be array that is ready to start looping (basically it must be in `['items']` array).
     * @return array Return associative array with keys:<br>
     *          `deleteSuccess` (bool) Delete result. `true` if success, `false` if failure.<br>
     *          `deletedItems` (int) The number of deleted items.<br>
     *          If contain error:<br>
     *              `errorMessage`(string) The thrown exception error message with trace as string.<br>
     *              `errcatch`(bool) This will be set to `true` if exception was thrown and catched.<br>
     */
    public function deleteTags(array $tidsArray): array
    {
        $TagsDb = new \Rdb\Modules\RdbCMSA\Models\TagsDb($this->Container);
        $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);
        $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);

        if ($this->Container->has('Logger')) {
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $Logger = $this->Container->get('Logger');
        }

        $output = [];
        $output['deleteSuccess'] = false;
        $output['deletedItems'] = 0;
        $tidsArrayQueueDelete = [];

        try {
            foreach ($tidsArray as $tid) {
                $deleteResult = $TagsDb->delete($tid, $this->taxonomyType);

                if ($deleteResult === true) {
                    $tidsArrayQueueDelete[] = (int) $tid;

                    $output['deletedItems']++;
                }

                unset($deleteResult);
            }// endforeach;
            unset($tid);

            if (isset($tidsArrayQueueDelete) && !empty($tidsArrayQueueDelete)) {
                // delete on url aliases table.
                $deleteUrlAlias = $UrlAliasesDb->deleteMultiple($this->taxonomyType, $tidsArrayQueueDelete);
                if ($deleteUrlAlias !== true) {
                    if (isset($Logger)) {
                        $Logger->write('modules/cms/controllers/admin/subcontrollers/taxonomytermdatasubcontroller', 2, 'The URL alias for taxonomy ids {tids} haven\'t been delete.', ['tids' => $tidsArrayQueueDelete]);
                    }
                }
                unset($deleteUrlAlias);

                // delete on translation matcher table.
                $deleteTM = $TranslationMatcherDb->deleteIfAllEmpty('taxonomy_term_data', $tidsArrayQueueDelete);
                if ($deleteTM === false) {
                    if (isset($Logger)) {
                        $Logger->write('modules/cms/controllers/admin/subcontrollers/taxonomytermdatasubcontroller', 2, 'The translation matchers for taxonomy ids {tids} haven\'t been delete.', ['tids' => $tidsArrayQueueDelete]);
                    }
                }
                unset($deleteTM);
            }

            $output['deleteSuccess'] = true;
        } catch (\Exception $ex) {
            $output['errorMessage'] = $ex->getMessage();
            $output['errcatch'] = true;
            $output['deleteSuccess'] = false;
        }// end try.

        unset($tidsArrayQueueDelete);

        unset($Logger, $TagsDb, $TranslationMatcherDb, $UrlAliasesDb);
        return $output;
    }// deleteTags


    /**
     * Validate category and action.
     * 
     * It's validating category and action must be selected.<br>
     * This method set HTTP response code if contain errors.<br>
     * This method was called from Category `ActionsController` -> `indexAction()`, `doDeleteAction()` methods.
     * 
     * @param string $tids The selected category ID(s).
     * @param string $action The selected action.
     * @param array $validateOptions Available options:<br>
     *                      `t_type` (string) The taxonomy type to check with get selected categories.
     * @return array Return associative array with keys:<br>
     *                          `action` The selected action.<br>
     *                          `actionText` The text of selected action, for displaying.<br>
     *                          `tids` The selected category IDs.<br>
     *                          `tid_array` The selected category IDs as array.<br>
     *                          `formResultStatus` (optional) If contain any error, it also send out http response code.<br>
     *                          `formResultMessage` (optional) If contain any error, it also send out http response code.<br>
     *                          `formValidated` The boolean value of form validation. It will be `true` if form validation passed, and will be `false` if it is not.<br>
     *                          `listSelectedCategories` The selected categories. Its structure is `array('total' => x, 'items' => array(...))`.
     */
    public function validateCategoryActions(string $tids, string $action, array $validateOptions = []): array
    {
        $output = [];

        $output['action'] = $action;
        $output['tids'] = $tids;
        $expTids = explode(',', $output['tids']);

        if (is_array($expTids)) {
            $output['tid_array'] = $expTids;
            $totalSelectedCategories = (int) count($expTids);
        } else {
            $output['tid_array'] = [];
            $totalSelectedCategories = 0;
        }
        unset($expTids);

        $formValidated = false;

        // validate selected category and action. ------------------------------
        if ($totalSelectedCategories <= 0) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = d__('rdbcmsa', 'Please select at least one category.');
        } else {
            $formValidated = true;
        }

        if (empty($output['action'])) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = __('Please select an action.');
            $formValidated = false;
        }
        // end validate selected category and action. --------------------------

        // set action text for display.
        if ($output['action'] === 'delete') {
            $output['actionText'] = dn__('rdbcmsa', 'Delete category', 'Delete categories', $totalSelectedCategories);
        } else {
            $output['actionText'] = $output['action'];
        }

        $CategoriesDb = new \Rdb\Modules\RdbCMSA\Models\CategoriesDb($this->Db->PDO(), $this->Container);
        // get selected categories.
        $options = [];
        $options['taxonomy_id_in'] = $output['tid_array'];
        $options['where'] = [
            't_type' => ($validateOptions['t_type'] ?? $this->taxonomyType),
        ];
        $output['listSelectedCategories'] = $CategoriesDb->listRecursive($options);
        unset($options);
        unset($CategoriesDb);

        $output['formValidated'] = $formValidated;

        unset($formValidated, $totalSelectedCategories);

        return $output;
    }// validateCategoryActions


}
