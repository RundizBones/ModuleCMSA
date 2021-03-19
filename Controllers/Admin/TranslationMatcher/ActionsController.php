<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\TranslationMatcher;


/**
 * Bulk actions controller.
 * 
 * @since 0.0.2
 */
class ActionsController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\TranslationMatcherTrait;


    public function doDeleteAction(string $tm_ids): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSATranslationMatcher', ['match']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getTMUrlsMethod();

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
            $tmIdsArray = $this->Input->delete('tm_id', []);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            if (empty($tm_ids) || !is_array($tmIdsArray) || implode(',', $tmIdsArray) !== $tm_ids) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'The selected translation matcher IDs does not matched.');
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
                $allowedActions = ['delete'];
                if (!in_array($bulkAction, $allowedActions)) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'Unallowed action detected.');
                    http_response_code(400);
                    $formValidated = false;
                }
                unset($allowedActions);
            }

            if ($formValidated === true) {
                $listTM = $this->listSelectedTM($tmIdsArray);
                if (!isset($listTM['items']) || !is_array($listTM['items']) || isset($listTM['formResultMessage'])) {
                    $output = array_merge($output, $listTM);
                    $formValidated = false;
                    unset($listTM);
                }
            }
            // end validate the form. --------------------------------------------------------------------

            if ($formValidated === true) {
                // if found selected item(s).
                if (defined('APP_ENV') && APP_ENV === 'development') {
                    $output['debug'] = [];
                    $output['debug']['listSelectedTMs'] = $listTM['items'];
                }

                $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);

                try {
                    $deleteResult = $TranslationMatcherDb->deleteMultiple($tmIdsArray);
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage() . '<br>' . $ex->getTraceAsString();
                    $output['errcatch'] = true;
                    $deleteResult = false;
                }

                if ($deleteResult === true) {
                    // if successfully deleted.
                    http_response_code(204);
                } else {
                    // if failed to delete.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to delete.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }
                unset($TranslationMatcherDb);
            }// endif; $formValidated
            unset($bulkAction, $formValidated, $tmIdsArray);
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
     * List selected translation matcher IDs.
     * 
     * @param array $tmIdsArray
     * @return array
     */
    protected function listSelectedTM(array $tmIdsArray): array
    {
        $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);
        $options = [];
        $options['tmIdsIn'] = $tmIdsArray;
        $options['unlimited'] = true;
        $result = $TranslationMatcherDb->listItems($options);
        unset($options, $TranslationMatcherDb);

        if (isset($result['items']) && is_array($result['items'])) {
             // if found selected item(s).
            return $result;
        } else {
            // if not found selected item(s).
            $output = [];
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = d__('rdbcmsa', 'Not found selected item.');
            http_response_code(404);
            return $output;
        }
    }// listSelectedTM


}
