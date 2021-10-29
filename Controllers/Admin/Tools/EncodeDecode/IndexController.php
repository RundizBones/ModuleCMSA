<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Tools\EncodeDecode;


/**
 * Encode/Decode tool controller.
 * 
 * @since 0.0.1
 */
class IndexController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    /**
     * Process the data.
     * 
     * @return string
     */
    public function doProcessAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAEncodeDecode', ['encode_decode']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validate csrf passed.
            unset($_POST[$csrfName], $_POST[$csrfValue]);

            // prepare data
            $data = [];
            $data['originalString'] = $this->Input->post('original-string', '', FILTER_UNSAFE_RAW);
            $data['direction'] = $this->Input->post('direction', 'encode');
            $data['useFunction'] = $this->Input->post('use-function');

            // validate form. -------------------------------------------------------------------------------
            if ($data['direction'] !== 'encode' && $data['direction'] !== 'decode') {
                $data['direction'] = 'encode';
            }
            // end validate form. --------------------------------------------------------------------------

            if (!empty(trim($data['originalString']))) {
                // if original string is not empty.
                $output['result'] = $data['originalString'];
                // determine function to use.
                if ($data['useFunction'] === 'base64') {
                    if ($data['direction'] === 'encode') {
                        $output['result'] = base64_encode($data['originalString']);
                    } else {
                        $result = base64_decode($data['originalString'], true);
                        if (false !== $result && false !== json_encode($result)) {
                            $output['result'] = $result;
                        }
                        unset($result);
                    }
                } elseif ($data['useFunction'] === 'rawurlencode_rawurldecode') {
                    if ($data['direction'] === 'encode') {
                        $output['result'] = rawurlencode($data['originalString']);
                    } else {
                        $output['result'] = rawurldecode($data['originalString']);
                    }
                } elseif ($data['useFunction'] === 'htmlspecialchars') {
                    if ($data['direction'] === 'encode') {
                        $output['result'] = htmlspecialchars($data['originalString'], ENT_QUOTES);
                    } else {
                        $output['result'] = htmlspecialchars_decode($data['originalString'], ENT_QUOTES);
                    }
                } elseif ($data['useFunction'] === 'htmlentities') {
                    if ($data['direction'] === 'encode') {
                        $output['result'] = htmlentities($data['originalString'], ENT_QUOTES);
                    } else {
                        $output['result'] = html_entity_decode($data['originalString'], ENT_QUOTES);
                    }
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
    }// doProcessAction


    /**
     * Display page action.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAEncodeDecode', ['encode_decode']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Encode/Decode');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();
        
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        // display, response part ---------------------------------------------------------------------------------------------
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

        $Assets->addMultipleAssets('js', ['rdbcmsaToolsEncodeDecodeIndexAction'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
        $Assets->addJsObject(
            'rdbcmsaToolsEncodeDecodeIndexAction',
            'RdbcmsaToolsEncodeDecodeIndexActionObject',
            [
                'csrfName' => $output['csrfName'],
                'csrfValue' => $output['csrfValue'],
                'csrfKeyPair' => $output['csrfKeyPair'],
            ]
        );

        $output['Assets'] = $Assets;
        $output['Modules'] = $this->Modules;
        $output['Url'] = $Url;
        $output['Views'] = $this->Views;
        $output['pageContent'] = $this->Views->render('Admin/Tools/EncodeDecode/index_v', $output);

        unset($Assets, $rdbAdminAssets, $Url);

        return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
    }// indexAction


}
