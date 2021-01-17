<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Posts;


/**
 * Revision history controller.
 * 
 * @since 0.0.1
 */
class RevisionController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\PostsTrait;


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Categories\Traits\CategoriesTrait;


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Tags\Traits\TagsTrait;


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
     * Delete selected revisions.
     * 
     * @global array $_DELETE
     * @param string $post_id The post ID.
     * @return string
     */
    public function doDeleteAction(string $post_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPosts', ['edit']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf(['persistentTokenMode' => true]);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $post_id = (int) $post_id;
        $output = [];
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make put data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if validate csrf passed.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            $PostRevisionDb = new \Rdb\Modules\RdbCMSA\Models\PostRevisionDb($this->Container);
            $revision_ids = $this->Input->delete('revision_id');
            $totalRevisionIds = (is_array($revision_ids) ? count($revision_ids) : 0);
            $deletedCount = 0;

            $PDO = $this->Db->PDO();
            $PDO->beginTransaction();

            foreach ($revision_ids as $revision_id) {
                try {
                    $resultRow = $this->PostsDb->get(['posts.post_id' => $post_id]);
                    if (is_object($resultRow) && $resultRow->revision_id == $revision_id) {
                        // if found selected revision ID is current revision.
                        // do not allow to delete this.
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = d__('rdbcmsa', 'Unable to delete the revision that is currently in use.');
                        http_response_code(400);
                        $deleteResult = false;
                        break;
                    } else {
                        // if revision ID is not matched current revision.
                        $deleteResult = $PostRevisionDb->delete(['post_id' => $post_id, 'revision_id' => $revision_id]);
                        if ($deleteResult === true) {
                            $deletedCount++;
                        } else {
                            break;
                        }
                    }
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage() . '<br>' . $ex->getTraceAsString();
                    $output['errcatch'] = true;
                    $deleteResult = false;
                }
            }// endforeach;
            unset($revision_id);

            unset($PostRevisionDb, $revision_ids);

            if (isset($deleteResult) && $deleteResult === true) {
                // if successfully deleted.
                $PDO->commit();
                if ($this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $Logger->write(
                        'modules/cms/controllers/admin/posts/revisioncontroller', 
                        0, 
                        'Total revision: {totalRevisionIds}, deleted count: {deletedCount}', 
                        [
                            'totalRevisionIds' => $totalRevisionIds,
                            'deletedCount' => $deletedCount,
                        ]
                    );
                    unset($Logger);
                }
                http_response_code(204);
            } else {
                // if failed to delete.
                $PDO->rollBack();
                if (!isset($output['formResultMessage'])) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to delete.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }
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
    }// doDeleteAction


    /**
     * Get a single revision content.
     * 
     * @param string $post_id The post ID.
     * @param string $revision_id The revision ID.
     * @return string
     */
    public function doGetRevisionAction(string $post_id, string $revision_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPosts', ['edit']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $post_id = (int) $post_id;
        $revision_id = (int) $revision_id;
        $output = [];

        $PostRevisionDb = new \Rdb\Modules\RdbCMSA\Models\PostRevisionDb($this->Container);
        $where = [
            'post_id' => $post_id,
            'revision_id' => $revision_id,
        ];
        $resultRow = $PostRevisionDb->get($where);
        unset($PostRevisionDb, $where);

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
        unset($Csrf, $Url);
        return $this->responseAcceptType($output);
    }// doGetRevisionAction


    public function doRollbackAction(string $post_id, string $revision_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPosts', ['edit']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $post_id = (int) $post_id;
        $revision_id = (int) $revision_id;
        $output = [];
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make put data into $_PUT variable.
        $this->Input->put('');
        global $_PUT;

        if (
            isset($_PUT[$csrfName]) &&
            isset($_PUT[$csrfValue]) &&
            $Csrf->validateToken($_PUT[$csrfName], $_PUT[$csrfValue])
        ) {
            // if validate csrf passed.
            unset($_PUT[$csrfName], $_PUT[$csrfValue]);

            $PostRevisionDb = new \Rdb\Modules\RdbCMSA\Models\PostRevisionDb($this->Container);
            $where = [
                'post_id' => $post_id,
                'revision_id' => $revision_id,
            ];
            $resultRow = $PostRevisionDb->get($where);
            unset($PostRevisionDb, $where);

            if (is_object($resultRow) && !empty($resultRow)) {
                // if found.
                // prepare data for selected post.
                $data = [];
                $data['revision_id'] = $revision_id;

                try {
                    $saveResult = $this->PostsDb->update($data, [], ['post_id' => $post_id]);
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage() . '<br>' . $ex->getTraceAsString();
                    $output['errcatch'] = true;
                    $saveResult = false;
                }

                if (isset($saveResult) && $saveResult === true) {
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Updated successfully.');
                    $output['rollbackSuccess'] = true;
                    http_response_code(200);
                } else {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to update.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }
            } else {
                // if not found.
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = d__('rdbcmsa', 'Not found selected item.');
                $output['result'] = null;
                http_response_code(404);
            }

            unset($resultRow);
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
    }// doRollbackAction


    /**
     * List revision history items action.
     * 
     * @param string $post_id The post ID.
     * @return string
     */
    public function indexAction(string $post_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPosts', ['edit']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $columns = $this->Input->get('columns', [], FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
        $order = $this->Input->get('order', [], FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
        $DataTablesJs = new \Rdb\Modules\RdbAdmin\Libraries\DataTablesJs();
        $sortOrders = $DataTablesJs->buildSortOrdersFromInput($columns, $order);
        unset($columns, $DataTablesJs, $order);

        $post_id = (int) $post_id;
        $output = [];

        $configDb = $this->getConfigDb();
        $PostRevisionDb = new \Rdb\Modules\RdbCMSA\Models\PostRevisionDb($this->Container);
        $options = [];
        $options['where'] = [
            'post_id' => $post_id,
        ];
        $options['sortOrders'] = $sortOrders;
        $options['offset'] = $this->Input->get('start', 0, FILTER_SANITIZE_NUMBER_INT);
        $options['limit'] = $this->Input->get('length', $configDb['rdbadmin_AdminItemsPerPage'], FILTER_SANITIZE_NUMBER_INT);
        $result = $PostRevisionDb->listItems($options);

        unset($configDb, $options, $PostRevisionDb);

        $output['draw'] = $this->Input->get('draw', 1, FILTER_SANITIZE_NUMBER_INT);
        $output['recordsTotal'] = ($result['total'] ?? 0);
        $output['recordsFiltered'] = $output['recordsTotal'];
        $output['listItems'] = ($result['items'] ?? []);

        // display, response part ---------------------------------------------------------------------------------------------
        return $this->responseAcceptType($output);
    }// indexAction


}
