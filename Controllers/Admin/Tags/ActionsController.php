<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Tags;


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


    use Traits\TagsTrait;


    /**
     * Do bulk actions.
     * 
     * @global array $_PATCH
     * @param string $tids The taxonomy IDs.
     * @return string
     */
    public function doActionsAction(string $tids): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentTags', ['delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getTagsUrlsMethod();

        // make delete data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (!isset($_PATCH['bulk-actions'])) {
            // if no action
            // don't waste time on this.
            return '';
        }

        $output['t_type'] = $this->Input->delete('t_type', $this->tagTaxonomyType);

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            $bulkAction = $this->Input->patch('bulk-actions');
            $tidsArray = $this->Input->patch('tid', []);
            $TagsDb = new \Rdb\Modules\RdbCMSA\Models\TagsDb($this->Container);
            $TaxonomyIndexDb = new \Rdb\Modules\RdbCMSA\Models\TaxonomyIndexDb($this->Container);

            if ($bulkAction === 'recount') {
                // if action is recount (update total items).
                if (is_array($tidsArray)) {
                    if (defined('APP_ENV') && APP_ENV === 'development') {
                        $output['debug'] = [];
                        $output['debug']['update'] = [];
                    }

                    foreach ($tidsArray as $tid) {
                        $t_total = $TaxonomyIndexDb->countTaxonomy((int) $tid);
                        $updateResult = $TagsDb->update(['t_total' => $t_total], ['tid' => $tid]);
                        if (defined('APP_ENV') && APP_ENV === 'development') {
                            $output['debug']['update'][(int) $tid] = [
                                't_total' => $t_total,
                                'updateResult' => $updateResult,
                            ];
                        }
                        unset($t_total, $updateResult);
                    }// endforeach;
                    unset($tid);

                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Updated successfully.');
                }
            }

            unset($bulkAction, $TagsDb, $TaxonomyIndexDb, $tidsArray);
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
     * delete action.
     * 
     * @param string $tids The IDs.
     * @return string
     */
    public function doDeleteAction(string $tids): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentTags', ['delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getTagsUrlsMethod();

        // make delete data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (!isset($_DELETE['bulk-actions'])) {
            // if no action
            // don't waste time on this.
            return '';
        }

        $output['t_type'] = $this->Input->delete('t_type', $this->tagTaxonomyType);

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            // prepare data
            $output['action'] = $this->Input->delete('bulk-actions');
            $output['tids'] = $tids;
            $output['tid_array'] = $this->Input->delete('tid');
            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            if (!is_array($output['tid_array']) || (is_array($output['tid_array']) && count($output['tid_array']) <= 0)) {
                http_response_code(400);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Please select at least one item.');
            } else {
                $formValidated = true;
            }

            if (empty($output['action'])) {
                http_response_code(400);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = __('Please select an action.');
                $formValidated = false;
            }
            // end validate the form. --------------------------------------------------------------------

            if ($formValidated === true) {
                // if form validation passed.
                $PDO = $this->Db->PDO();
                $PDO->beginTransaction();
                $TagsDb = new \Rdb\Modules\RdbCMSA\Models\TagsDb($this->Container);
                $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);
                $deletedItems = 0;

                try {
                    foreach ($output['tid_array'] as $tid) {
                        $deleteResult = $TagsDb->delete($tid, $output['t_type']);

                        if ($deleteResult === true) {
                            $deleteUrlAlias = $UrlAliasesDb->delete([
                                'alias_content_type' => $output['t_type'],
                                'alias_content_id' => $tid,
                            ]);

                            if ($deleteUrlAlias !== true) {
                                if ($this->Container->has('Logger')) {
                                    /* @var $Logger \Rdb\System\Libraries\Logger */
                                    $Logger = $this->Container->get('Logger');
                                    $Logger->write('modules/cms/controllers/admin/tags/actionscontroller', 2, 'The URL alias for taxonomy id {tid} hasn\'t been delete.', ['tid' => $tid]);
                                    unset($Logger);
                                }
                            }

                            $deletedItems++;
                            unset($deleteResult, $deleteUrlAlias);
                        }
                    }// endforeach;
                    unset($tid);
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage();
                    $PDO->rollBack();
                    $deletedItems = 0;
                    $containError = true;
                }// end try.

                if (!isset($containError) || (isset($containError) && $containError !== true)) {
                    $PDO->commit();
                    $output['deletedItems'] = $deletedItems;
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Deleted successfully.');
                    http_response_code(200);

                    $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                    unset($output['formResultMessage'], $output['formResultStatus']);
                    $output['redirectBack'] = $output['urls']['getTagsUrl'] . '?filter-t_type=' . rawurlencode($output['t_type']);
                } else {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to delete.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }

                unset($containError, $deletedItems, $PDO, $TagsDb, $UrlAliasesDb);
            }

            unset($formValidated);
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


}