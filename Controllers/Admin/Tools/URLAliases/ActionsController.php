<?php


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Tools\URLAliases;


/**
 * URL aliases actions controller.
 * 
 * @since 0.0.1
 */
class ActionsController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\URLAliasesTrait;


    /**
     * delete action.
     * 
     * @return string
     */
    public function doDeleteAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAUrlAliases', ['delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getAliasesUrlsMethod();

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

            $idArray = $this->Input->delete('alias_id', []);
            if (is_array($idArray)) {
                $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);
                $count = 0;
                foreach ($idArray as $alias_id) {
                    $deleteResult = $UrlAliasesDb->delete(['alias_id' => $alias_id]);
                    if ($deleteResult === true) {
                        $count++;
                    }
                }// endforeach;
                unset($alias_id, $deleteResult, $UrlAliasesDb);
            }
            unset($idArray);

            if (isset($count) && $count > 0) {
                $output['formResultStatus'] = 'success';
                $output['formResultMessage'] = __('Deleted successfully.');
                $output['totalDeleted'] = $count;
                http_response_code(204);
            } else {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = d__('rdbcmsa', 'Unable to delete.');
                $output['totalDeleted'] = $count;
                http_response_code(500);
            }
            // END TODO
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
