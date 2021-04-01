<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Pages;


/**
 * Bulk actions controller.
 * 
 * @since 0.0.1
 */
class ActionsController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\PagesTrait;


    /**
     * @var \Rdb\Modules\RdbCMSA\Models\PostsDb $PostsDb 
     */
    protected $PostsDb;


    /**
     * @since 0.0.5
     * @var \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\PostsSubControlle
     */
    protected $PostsSubController;


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

        $this->PostsSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\PostsSubController($this->Container);
        $this->PostsSubController->categoryType = $this->categoryType;
        $this->PostsSubController->postType = $this->postType;
        $this->PostsSubController->tagType = $this->tagType;
        $this->PostsSubController->PostsDb = $this->PostsDb;
    }// __construct


    /**
     * Do multiple actions that is not permanently delete.
     * 
     * @param string $post_ids The IDs.
     * @return string
     */
    public function doActionsAction(string $post_ids): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPages', ['delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getPostsUrlsMethod();

        // make delete data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (!isset($_PATCH['bulk-actions'])) {
            // if no action
            // don't waste time on this.
            return '';
        }

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            $bulkAction = trim($this->Input->patch('bulk-actions'));
            $postIdsArray = $this->Input->patch('post_id', []);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            if (empty($post_ids) || !is_array($postIdsArray) || implode(',', $postIdsArray) !== $post_ids) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'The selected post IDs does not matched.');
                http_response_code(400);
                $formValidated = false;
            } else {
                $formValidated = true;
            }

            if (empty($bulkAction)) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = __('Please select an action.');
                http_response_code(400);
                $formValidated = false;
            }

            if ($formValidated === true) {
                $allowedActions = ['trash', 'restore'];
                if (!in_array($bulkAction, $allowedActions)) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'Unallowed action detected.');
                    http_response_code(400);
                    $formValidated = false;
                }
                unset($allowedActions);
            }

            if ($formValidated === true) {
                $listPosts = $this->listSelectedPosts($postIdsArray);
                if (!isset($listPosts['items']) || !is_array($listPosts['items']) || isset($listPosts['formResultMessage'])) {
                    $output = array_merge($output, $listPosts);
                    $formValidated = false;
                    unset($listPosts);
                }
            }
            // end validate the form. --------------------------------------------------------------------

            if ($formValidated === true) {
                // do bulk actions (that is not permanently delete) in sub controller.
                $bulkActionResult = $this->PostsSubController->bulkActionsPatch($bulkAction, $postIdsArray, $listPosts);
                $saveResult = $bulkActionResult['saveResult'];
                unset($bulkActionResult['saveResult']);
                $output = array_merge($output, $bulkActionResult);

                if (isset($saveResult) && $saveResult === true) {
                    // if update success.
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Updated successfully.');
                    http_response_code(200);
                } else {
                    // if update failed.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to update.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }
            }// endif; $formValidated

            unset($bulkAction, $formValidated, $postIdsArray);
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
    }// doActionsAction


    /**
     * permanently delete action.
     * 
     * @param string $post_ids The IDs.
     * @return string
     */
    public function doDeleteAction(string $post_ids): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPages', ['delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getPostsUrlsMethod();

        // make delete data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (!isset($_DELETE['bulk-actions'])) {
            // if no action
            // don't waste time on this.
            return '';
        }

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            $bulkAction = trim($this->Input->delete('bulk-actions'));
            $postIdsArray = $this->Input->delete('post_id', []);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            if (empty($post_ids) || !is_array($postIdsArray) || implode(',', $postIdsArray) !== $post_ids) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'The selected post IDs does not matched.');
                http_response_code(400);
                $formValidated = false;
            } else {
                $formValidated = true;
            }

            if (empty($bulkAction)) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = __('Please select an action.');
                http_response_code(400);
                $formValidated = false;
            }

            if ($formValidated === true) {
                $allowedActions = ['delete_permanently'];
                if (!in_array($bulkAction, $allowedActions)) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'Unallowed action detected.');
                    http_response_code(400);
                    $formValidated = false;
                }
                unset($allowedActions);
            }

            if ($formValidated === true) {
                $listPosts = $this->listSelectedPosts($postIdsArray);
                if (!isset($listPosts['items']) || !is_array($listPosts['items']) || isset($listPosts['formResultMessage'])) {
                    $output = array_merge($output, $listPosts);
                    $formValidated = false;
                    unset($listPosts);
                }
            }
            // end validate the form. --------------------------------------------------------------------

            if ($formValidated === true) {
                // if found selected item(s).
                if (defined('APP_ENV') && APP_ENV === 'development') {
                    $output['debug'] = [];
                    $output['debug']['listSelectedPosts'] = $listPosts['items'];
                }

                foreach ($listPosts['items'] as $row) {
                    if ((int) $row->post_status !== 5) {
                        // if the post status is not trashed.
                        // show error message, return response code, break for each, set false for validation.
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = sprintf(d__('rdbcmsa', 'The selected post is not in trash (ID %d).'), $row->post_id);
                        http_response_code(400);
                        $formValidated = false;
                        break;
                    }
                }// endforeach;
                unset($row);

                if ($formValidated === true) {
                    // check again that all posts are validated for correct status.
                    $PDO = $this->Db->PDO();
                    $PDO->beginTransaction();

                    $outputDelete = $this->PostsSubController->deletePosts($postIdsArray);
                    $deleteResult = ($outputDelete['deleteResult'] ?? false);
                    unset($outputDelete['deleteResult']);
                    $output = array_merge($output, $outputDelete);
                    unset($outputDelete);

                    if ($deleteResult === true) {
                        // if successfully deleted.
                        $PDO->commit();
                        http_response_code(204);
                    } else {
                        // if failed to delete.
                        $PDO->rollBack();
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = d__('rdbcmsa', 'Unable to delete.');
                        if (isset($output['errorMessage'])) {
                            $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                        }
                        http_response_code(500);
                    }
                }
            }// endif; $formValidated

            unset($bulkAction, $formValidated, $postIdsArray);
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
     * List selected pages.
     * 
     * If not found then it will be return array keys (see @return) and send HTTP response code.
     * 
     * This method was called from `doActionsAction()`, `doDeleteAction()` methods.
     * 
     * @param array $postIdsArray The selected post IDs as array.
     * @return array Return array with `total`, `items` keys on success,<br>
     *                      Or array with `formResultMessage`, `formResultStatus` keys on failure.
     */
    protected function listSelectedPosts(array $postIdsArray): array
    {
        $options = [];
        $options['postidsIn'] = $postIdsArray;
        $options['unlimited'] = true;
        $options['skipCategories'] = true;
        $options['skipTags'] = true;
        $listPosts = $this->PostsDb->listItems($options);
        unset($options);

        if (isset($listPosts['items']) && is_array($listPosts['items'])) {
            // if found selected item(s).
            return $listPosts;
        } else {
            // if not found selected item(s).
            $output = [];
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = d__('rdbcmsa', 'Not found selected item.');
            http_response_code(404);
            return $output;
        }
    }// listSelectedPosts


}
