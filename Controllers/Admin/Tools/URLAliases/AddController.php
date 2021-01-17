<?php


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Tools\URLAliases;


/**
 * Add URL alias controller.
 * 
 * @since 0.0.1
 */
class AddController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\URLAliasesTrait;


    /**
     * Add an URL alias.
     * 
     * @return string
     */
    public function doAddAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAUrlAliases', ['add']);

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

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            // prepare data for checking.
            $data = [];
            $data['alias_content_type'] = trim($this->Input->post('alias_content_type', '', FILTER_SANITIZE_STRING));
            $data['alias_content_id'] = trim($this->Input->post('alias_content_id', '', FILTER_SANITIZE_NUMBER_INT));
            $data['language'] = ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th');
            $data['alias_url'] = trim($this->Input->post('alias_url', null));
            $data['alias_redirect_to'] = trim($this->Input->post('alias_redirect_to', null));
            $data['alias_redirect_code'] = trim($this->Input->post('alias_redirect_code', null));
            // set null if empty.
            $InputUtils = new \Rdb\Modules\RdbCMSA\Libraries\InputUtils();
            $data = $InputUtils->setEmptyScalarToNull($data);
            unset($InputUtils);

            $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            if (empty($data['alias_url'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Please enter the URL alias.');
                http_response_code(400);
                $formValidated = false;
            } else {
                $formValidated = true;
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

            if ($formValidated === true) {
                // check for duplicated URL from URL aliases.
                $isDuplicated = $UrlAliasesDb->isDuplicatedUrl($data['alias_url'], $data['language']);
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
                    $alias_id = $UrlAliasesDb->add($data);
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage();
                    $alias_id = false;
                }

                if ($alias_id !== false) {
                    // if success to add.
                    $output['alias_id'] = $alias_id;
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Added successfully.');
                    http_response_code(201);

                    $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                    unset($output['formResultMessage'], $output['formResultStatus']);
                    $output['redirectBack'] = $output['urls']['getAliasesUrl'];
                } else {
                    // if failed to add.
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to add new URL alias.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }
                // END TODO
            }

            unset($data, $formValidated, $UrlAliasesDb);
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


    /**
     * Add page action.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAUrlAliases', ['add']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Add an URL alias');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

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
                'link' => $output['urls']['addAliasUrl'],
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
            $Assets->addMultipleAssets('js', ['rdbcmsaToolsURLAliasesAddAction', 'rdbaHistoryState'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaToolsURLAliasesAddAction',
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
            $output['pageContent'] = $this->Views->render('Admin/Tools/URLAliases/add_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
