<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\TranslationMatcher;


/**
 * Add translation matcher.
 * 
 * @since 0.0.2
 */
class AddController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\TranslationMatcherTrait;


    use Traits\EditingTrait;


    /**
     * Add translation matches.
     * 
     * @return string
     */
    public function doAddAction(): string
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

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            // prepare data for checking.
            $data = [];
            $data['tm_table'] = trim($this->Input->post('tm_table'));
            $data['matches'] = $this->Input->post('matches');

            $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            $output = array_merge($output, $this->validateForm($data, $formValidated));

            if (true === $formValidated) {
                // find that adding id must not exists in db.
                $findDataIds = [];
                foreach ($data['matches'] as $languageId => $data_id) {
                    if (!empty($data_id)) {
                        $findDataIds[] = (int) $data_id;
                    }
                }// endforeach;
                unset($data_id, $languageId);
                $tmTable = $data['tm_table'];
                if ($TranslationMatcherDb->isIdsExists($findDataIds, $tmTable) === true) {
                    $formValidated = false;
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'The data you had entered is already exists.');
                    http_response_code(400);
                }// endif;
                unset($findDataIds, $tmTable);
            }
            // end validate the form. --------------------------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                try {
                    $tm_id = $TranslationMatcherDb->add($data);
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage();
                    $output['errcatch'] = true;
                    $tm_id = false;
                }

                if ($tm_id !== false && $tm_id > 0) {
                    // if success.
                    $output['savedSuccess'] = true;
                    $output['tm_id'] = $tm_id;
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Added successfully.');
                    http_response_code(201);
                } else {
                    // if failed.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to add new matches.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }
            }// endif;
            unset($data, $formValidated, $TranslationMatcherDb);
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
    }// doAddAction


}
