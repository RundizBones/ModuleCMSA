<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\TranslationMatcher;


/**
 * Edit translation matcher.
 * 
 * @since 0.0.2
 */
class EditController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\TranslationMatcherTrait;


    use Traits\EditingTrait;


    /**
     * Do update data.
     * 
     * @param string $tm_id
     * @return string
     */
    public function doUpdateAction(string $tm_id): string
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

        $tm_id = (int) $tm_id;
        // make patch data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validate csrf passed.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            // prepare data for checking.
            $data = [];
            $data['tm_table'] = trim($this->Input->patch('tm_table'));
            $data['matches'] = $this->Input->patch('matches');

            $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            $result = $TranslationMatcherDb->get(['tm_id' => $tm_id]);
            if (!is_object($result) || empty($result) || empty($result->tm_id)) {
                $formValidated = false;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'The translation matches data you are editing is not exists.');
                http_response_code(404);
            } else {
                $formValidated = true;
            }

            if (true === $formValidated) {
                $output = array_merge($output, $this->validateForm($data, $formValidated));
            }

            if (true === $formValidated) {
                // find that editing id must not exists in db.
                $findDataIds = [];
                foreach ($data['matches'] as $languageId => $data_id) {
                    if (!empty($data_id)) {
                        $findDataIds[] = (int) $data_id;
                    }
                }// endforeach;
                unset($data_id, $languageId);
                $TranslationMatcherDb->isIdsExistsButNotInTmID($tm_id, $findDataIds, $data['tm_table']);
                $tmResult = [
                    'items' => $TranslationMatcherDb->isIdsExistsButNotInTmIDResult
                ];
                $output = array_merge($output, $this->validateIsIdExistsLang($tmResult, $data['matches'], $formValidated));
                unset($findDataIds, $tmResult);
            }
            // end validate the form. --------------------------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                try {
                    /*
                    * PluginHook: Rdb\Modules\RdbCMSA\Controllers\Admin\TranslationMatcher\EditController->doUpdateAction.beforeUpdate
                    * PluginHookDescription: Hook on update translation matcher succeeded.
                    * PluginHookParam: <br>
                    *      object|false $result The result row of fetched data from `translation_matcher` table where the ID is editing translation.
                    * PluginHookSince: 0.0.15
                    */
                   /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
                   $Plugins = $this->Container->get('Plugins');
                   $Plugins->doHook(__CLASS__.'->'.__FUNCTION__.'.beforeUpdate', [$result]);
                   unset($Plugins);

                    $saveResult = $TranslationMatcherDb->update($data, ['tm_id' => $tm_id]);
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage();
                    $output['errcatch'] = true;
                    $saveResult = false;
                }

                if (isset($saveResult) && $saveResult === true) {
                    $output['savedSuccess'] = true;
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Updated successfully.');
                    http_response_code(200);
                } else {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to update.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    if (defined('APP_ENV') && APP_ENV === 'development') {
                        $output['debug']['update'] = $TranslationMatcherDb->debugUpdate;
                    }
                    http_response_code(500);
                }
                unset($saveResult);
            }// endif; $formValidated
            unset($data, $formValidated, $result, $TranslationMatcherDb);
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
    }// doUpdateAction


}
