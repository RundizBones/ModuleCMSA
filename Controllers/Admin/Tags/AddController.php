<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Tags;


/**
 * Add controller.
 * 
 * @since 0.0.1
 */
class AddController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\TagsTrait;


    /**
     * Add an item.
     * 
     * @return string
     */
    public function doAddAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentTags', ['add']);

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

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            // prepare data for checking.
            $data = [];
            $dataUrlAliases = [];
            $data['t_type'] = $this->tagTaxonomyType;
            $data['t_name'] = trim($this->Input->post('t_name', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $data['t_description'] = trim($this->Input->post('t_description'));
            // set null if empty.
            $InputUtils = new \Rdb\Modules\RdbCMSA\Libraries\InputUtils();
            $data = $InputUtils->setEmptyScalarToNull($data);
            unset($InputUtils);
            $data['language'] = ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th');
            if (isset($_POST['alias_url']) && !empty(trim($_POST['alias_url']))) {
                $dataUrlAliases['alias_content_type'] = $data['t_type'];
                $dataUrlAliases['language'] = $data['language'];
                $dataUrlAliases['alias_url'] = $this->Input->post('alias_url', null);
            }

            $TagsDb = new \Rdb\Modules\RdbCMSA\Models\TagsDb($this->Container);
            $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);
            $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            if (empty($data['t_name'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Please enter the name.');
                http_response_code(400);
                $formValidated = false;
            } else {
                $formValidated = true;
            }

            if ($formValidated === true && !empty($dataUrlAliases)) {
                // check for duplicated URL from URL aliases.
                $isDuplicated = $UrlAliasesDb->isDuplicatedUrl($dataUrlAliases['alias_url'], $dataUrlAliases['language']);
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

            if ($formValidated === true && !empty($_POST['translation-matcher-from-tid'])) {
                // if there is data to create new translation from source ID.
                $tmData = [];
                $tmData['fromTid'] = $this->Input->post('translation-matcher-from-tid', '', FILTER_SANITIZE_NUMBER_INT);
                $tmData['tmTable'] = 'taxonomy_term_data';

                if (!is_numeric($tmData['fromTid'])) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'Invalid translation matcher from source ID.');
                    http_response_code(400);
                    $formValidated = false;
                }// endif; check from tid

                if ($formValidated === true) {
                    $TaxonomyTermDataDb = new \Rdb\Modules\RdbCMSA\Models\TaxonomyTermDataDb($this->Container);
                    $fromTidResult = $TaxonomyTermDataDb->get(['tid' => intval($tmData['fromTid'])]);
                    if (empty($fromTidResult) || $fromTidResult === false) {
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'][] = d__('rdbcmsa', 'Could not found the ID from source.');
                        http_response_code(404);
                        $formValidated = false;
                    } else {
                        $tmData['fromTidLanguage'] = $fromTidResult->language;
                    }
                    unset($fromTidResult, $TaxonomyTermDataDb);
                }

                if ($formValidated === true && $TranslationMatcherDb->isCurrentLangEmpty(intval($tmData['fromTid']), $tmData['tmTable']) === false) {
                        // if current language of selected id is not empty.
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'][] = d__('rdbcmsa', 'The translation you are trying to match is already exists.');
                        http_response_code(400);
                        $formValidated = false;
                }
            }// endif; form validated for translation matcher.
            // end validate the form. --------------------------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                try {
                    $tid = $TagsDb->add($data);

                    if ($tid !== false && $tid > 0 && !empty($dataUrlAliases)) {
                        $dataUrlAliases['alias_content_id'] = $tid;
                        $UrlAliasesDb->add($dataUrlAliases);
                    }
                    unset($dataUrlAliases);
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage();
                    $tid = false;
                    $output['errcatch'] = true;
                }

                if ($tid !== false && $tid > '0') {
                    // if add success.
                    if (isset($tmData)) {
                        // if there is translation matcher functional here.
                        // try to add or update the new id of current language with the id from previous (link clicked).
                        $output['addTranslationResult'] = $TranslationMatcherDb->addUpdateWithSource(
                            [$tmData['fromTidLanguage'] => $tmData['fromTid']], 
                            [$_SERVER['RUNDIZBONES_LANGUAGE'] => $tid], 
                            $tmData['tmTable']
                        );
                        unset($tmData);
                    }// endif; there is translation matcher data to be add.

                    $output['tid'] = $tid;
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Added successfully.');
                    http_response_code(201);

                    $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                    unset($output['formResultMessage'], $output['formResultStatus']);
                    $output['redirectBack'] = $output['urls']['getTagsUrl'];
                } else {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to add new tag.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }
                unset($tid);
            }

            unset($data, $dataUrlAliases, $formValidated, $tmData);
            unset($TranslationMatcherDb, $TagsDb, $UrlAliasesDb);
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
     * Add a tag page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentTags', ['add']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Add a tag');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $output['urls'] = $this->getTagsUrlsMethod();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        $output['baseUrl'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true);
        $output['t_type'] = $this->tagTaxonomyType;

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
                'link' => $output['urls']['addTagUrl'],
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
            $Assets->addMultipleAssets('js', ['rdbcmsaTagsAddAction', 'rdbaHistoryState'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaTagsAddAction',
                'RdbCMSATagsIndexObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
                    'baseUrl' => $Url->getAppBasedPath(true),
                    't_type' => $output['t_type'],
                ], $this->getTagsUrlsMethod())
            );

            // include html functions file to use `renderBreadcrumbHtml()` function.
            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Tags/add_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
