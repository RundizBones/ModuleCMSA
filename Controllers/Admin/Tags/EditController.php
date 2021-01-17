<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Tags;


/**
 * Edit controller.
 * 
 * @since 0.0.1
 */
class EditController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\TagsTrait;


    /**
     * Do update record.
     * 
     * @global array $_PATCH
     * @param string $tid
     * @return string
     */
    public function doUpdateAction(string $tid): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentTags', ['edit']);

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

        // make patch data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validate csrf passed.
            $tid = (int) $tid;
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            // prepare data for checking.
            $data = [];
            $dataUrlAliases = [];
            $data['t_name'] = trim($this->Input->patch('t_name', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $data['t_description'] = trim($this->Input->patch('t_description', null));
            $dataUrlAliases['alias_url'] = $this->Input->patch('alias_url', null);

            // set null if empty.
            $InputUtils = new \Rdb\Modules\RdbCMSA\Libraries\InputUtils();
            $data = $InputUtils->setEmptyScalarToNull($data);
            $dataUrlAliases = $InputUtils->setEmptyScalarToNull($dataUrlAliases);
            unset($InputUtils);

            $TagsDb = new \Rdb\Modules\RdbCMSA\Models\TagsDb($this->Container);
            $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            $resultRow = $TagsDb->get(['tid' => $tid]);
            if (is_object($resultRow) && !empty($resultRow)) {
                $formValidated = true;
            } else {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Not found selected tag.');
                http_response_code(404);
                $formValidated = false;
            }

            if ($formValidated === true) {
                if (empty($data['t_name'])) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'Please enter the name.');
                    http_response_code(400);
                    $formValidated = false;
                }
            }

            if ($formValidated === true && !empty($dataUrlAliases['alias_url'])) {
                // check for duplicated URL from URL aliases.
                $isDuplicated = $UrlAliasesDb->isDuplicatedUrl(
                    $dataUrlAliases['alias_url'], 
                    $resultRow->language,
                    $tid,
                    $resultRow->t_type
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
                    $saveResult = $TagsDb->update($data, ['tid' => $tid]);

                    if ($saveResult === true) {
                        if (empty($dataUrlAliases['alias_url'])) {
                            // url alias for this maybe removed.
                            $UrlAliasesDb->delete([
                                'language' => $resultRow->language, 
                                'alias_content_type' => $resultRow->t_type, 
                                'alias_content_id' => $tid,
                            ]);
                        } else {
                            $dataUrlAliases['language'] = $resultRow->language;
                            $dataUrlAliases['alias_content_type'] = $resultRow->t_type;
                            $dataUrlAliases['alias_content_id'] = $tid;
                            $UrlAliasesDb->addOrUpdate($dataUrlAliases, ['alias_content_type' => $resultRow->t_type, 'alias_content_id' => $tid]);
                        }
                    }
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
                    $output['redirectBack'] = $output['urls']['getTagsUrl'] . '?filter-t_type=' . $resultRow->t_type;
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
     * @param string $tid The ID matched `tid` column in DB.
     * @return string
     */
    public function indexAction(string $tid): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentTags', ['edit']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Edit a tag');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $output['tid'] = (int) $tid;
        $output['urls'] = $this->getTagsUrlsMethod();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        $output['baseUrl'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true);

        $urlBaseWithLang = $Url->getAppBasedPath(true);
        $output['breadcrumb'] = [
            [
                'item' => __('Admin home'),
                'link' => $urlBaseWithLang . '/admin',
            ],
            [
                'item' => d__('rdbcmsa', 'Contents'),
                'link' => $urlBaseWithLang . '/admin/cms/posts',
            ],
            [
                'item' => d__('rdbcmsa', 'Tags'),
                'link' => $output['urls']['getTagsUrl'],
            ],
            [
                'item' => $output['pageTitle'],
                'link' => $output['urls']['editTagUrlBase'] . '/' . $tid,
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
            $Assets->addMultipleAssets('js', ['rdbcmsaTagsEditAction', 'rdbaHistoryState'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaTagsEditAction',
                'RdbCMSATagsIndexObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                ], $this->getTagsUrlsMethod())
            );

            // include html functions file to use `renderBreadcrumbHtml()` function.
            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Tags/edit_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
