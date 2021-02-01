<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Tools\URLAliases;


/**
 * Edit URL alias controller.
 * 
 * @since 0.0.1
 */
class EditController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\URLAliasesTrait;


    /**
     * Do update record.
     * 
     * @global array $_PATCH
     * @param string $alias_id
     * @return string
     */
    public function doUpdateAction(string $alias_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAUrlAliases', ['edit']);

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

        // make patch data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validate csrf passed.
            $alias_id = (int) $alias_id;
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            // prepare data for checking.
            $data = [];
            $data['alias_content_type'] = trim($this->Input->patch('alias_content_type', '', FILTER_SANITIZE_STRING));
            $data['alias_content_id'] = trim($this->Input->patch('alias_content_id', '', FILTER_SANITIZE_NUMBER_INT));
            $data['alias_url'] = trim($this->Input->patch('alias_url', null));
            $data['alias_redirect_to'] = trim($this->Input->patch('alias_redirect_to', null));
            $data['alias_redirect_code'] = trim($this->Input->patch('alias_redirect_code', null));
            // set null if empty.
            $InputUtils = new \Rdb\Modules\RdbCMSA\Libraries\InputUtils();
            $data = $InputUtils->setEmptyScalarToNull($data);
            unset($InputUtils);

            $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            $resultRow = $UrlAliasesDb->get(['alias_id' => $alias_id]);
            if (is_object($resultRow) && !empty($resultRow)) {
                $formValidated = true;
            } else {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Not found selected URL alias.');
                http_response_code(404);
                $formValidated = false;
            }

            if ($formValidated === true) {
                if (empty($data['alias_url'])) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'Please enter the URL alias.');
                    http_response_code(400);
                    $formValidated = false;
                }

                if (
                    (
                        empty($data['alias_redirect_to']) && 
                        (
                            empty($data['alias_content_type']) ||
                            empty($data['alias_content_id'])
                        )
                    ) ||
                    (
                        !empty($data['alias_redirect_to']) && 
                        (
                            !empty($data['alias_content_type']) || 
                            !empty($data['alias_content_id'])
                        )
                    )
                ) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'Please choose to enter the required fields to work as URL alias or URL redirection.');
                    http_response_code(400);
                    $formValidated = false;
                }
            }

            if ($formValidated === true) {
                // check for duplicated URL from URL aliases.
                $isDuplicated = $UrlAliasesDb->isDuplicatedUrl(
                    $data['alias_url'], 
                    $resultRow->language,
                    (int) $resultRow->alias_content_id,
                    (string) $resultRow->alias_content_type,
                    $alias_id
                );
                if ($isDuplicated !== false) {
                    // if URL is already exists.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'The URL is already exists.');
                    $output['duplicatedUrlNotFound'] = false;
                    $output['duplicatedIn'] = 'url_aliases';
                    http_response_code(400);
                    $formValidated = false;
                } else {
                    $output['duplicatedUrlNotFound'] = true;
                }
                unset($isDuplicated);
            }
            // end validate the form. --------------------------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                try {
                    $saveResult = $UrlAliasesDb->update($data, ['alias_id' => $alias_id]);
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage();
                    $output['errcatch'] = true;
                    $saveResult = false;
                }

                if ($saveResult === true) {
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Updated successfully.');
                    http_response_code(200);

                    $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                    unset($output['formResultMessage'], $output['formResultStatus']);
                    $output['redirectBack'] = $output['urls']['getAliasesUrl'];
                } else {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to update.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }
                unset($saveResult);
            }// endif; $formValidated

            unset($data, $formValidated, $resultRow, $TagsDb, $UrlAliasesDb);
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


    /**
     * Edit page action.
     * 
     * @param string $alias_id The ID matched `alias_id` column in DB.
     * @return string
     */
    public function indexAction(string $alias_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAUrlAliases', ['edit']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Edit an URL alias');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $output['alias_id'] = (int) $alias_id;
        $output['urls'] = $this->getAliasesUrlsMethod();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        $output['baseUrl'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true);
        $output['appBasePath'] = $Url->getAppBasedPath(true);

        $urlBaseWithLang = $Url->getAppBasedPath(true);
        $output['breadcrumb'] = [
            [
                'item' => __('Admin home'),
                'link' => $urlBaseWithLang . '/admin',
            ],
            [
                'item' => __('Tools'),
                'link' => $urlBaseWithLang . '/admin/tools',
            ],
            [
                'item' => d__('rdbcmsa', 'URL Aliases'),
                'link' => $output['urls']['getAliasesUrl'],
            ],
            [
                'item' => $output['pageTitle'],
                'link' => $output['urls']['editAliasUrlBase'] . '/' . $alias_id,
            ],
        ];
        unset($urlBaseWithLang);

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
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

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage'], $Assets->mergeAssetsData('css', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addMultipleAssets('js', ['rdbcmsaToolsURLAliasesEditAction', 'rdbaHistoryState'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaToolsURLAliasesEditAction',
                'RdbCMSAToolsURLAliasesIndexObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                ], $this->getAliasesUrlsMethod())
            );

            // include html functions file to use `renderBreadcrumbHtml()` function.
            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Tools/URLAliases/edit_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
