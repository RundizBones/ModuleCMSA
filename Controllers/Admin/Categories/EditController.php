<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Categories;


/**
 * Edit category controller.
 * 
 * @since 0.0.1
 */
class EditController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\CategoriesTrait;


    public function doUpdateAction($tid): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentCategories', ['edit']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getCategoriesUrlMethods();

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
            $data['parent_id'] = trim($this->Input->patch('parent_id', 0, FILTER_SANITIZE_NUMBER_INT));
            $data['t_name'] = trim($this->Input->patch('t_name', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $data['t_description'] = trim($this->Input->patch('t_description', null));
            $data['t_status'] = trim($this->Input->patch('t_status', 0, FILTER_SANITIZE_NUMBER_INT));
            $data['t_position'] = trim($this->Input->patch('t_position', 1, FILTER_SANITIZE_NUMBER_INT));
            $dataUrlAliases['alias_url'] = $this->Input->patch('alias_url', null);

            // set null if empty.
            $InputUtils = new \Rdb\Modules\RdbCMSA\Libraries\InputUtils();
            $data = $InputUtils->setEmptyScalarToNull($data);
            $dataUrlAliases = $InputUtils->setEmptyScalarToNull($dataUrlAliases);
            unset($InputUtils);

            $CategoriesDb = new \Rdb\Modules\RdbCMSA\Models\CategoriesDb($this->Db->PDO(), $this->Container);
            $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            $categoryRow = $CategoriesDb->get(['tid' => $tid]);
            if (is_object($categoryRow) && !empty($categoryRow)) {
                $formValidated = true;
            } else {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Not found selected category.');
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
                    $categoryRow->language,
                    $tid,
                    $categoryRow->t_type
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

            if ($formValidated === true) {
                if ($data['parent_id'] == $tid || $CategoriesDb->isParentUnderMyChildren($tid, $data['parent_id']) !== false) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'The parent category must not under the children of editing category.');
                    http_response_code(400);
                    $formValidated = false;
                }
            }

            if ($formValidated === true) {
                if (empty($data['t_position']) || !is_numeric($data['t_position']) || $data['t_position'] <= 0) {
                    $data['t_position'] = $CategoriesDb->getNewPosition(
                        $data['parent_id'], 
                        [
                            'whereString' => 't_type = :t_type',
                            'whereValues' => [':t_type' => $categoryRow->t_type],
                        ]
                    );
                }
            }
            // end validate the form. --------------------------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                try {
                    $saveResult = $CategoriesDb->update($data, ['tid' => $tid]);

                    if ($saveResult === true) {
                        if (empty($dataUrlAliases['alias_url'])) {
                            // url alias for this maybe removed.
                            $UrlAliasesDb->delete([
                                'language' => $categoryRow->language, 
                                'alias_content_type' => $categoryRow->t_type, 
                                'alias_content_id' => $tid,
                            ]);
                        } else {
                            $dataUrlAliases['language'] = $categoryRow->language;
                            $dataUrlAliases['alias_content_type'] = $categoryRow->t_type;
                            $dataUrlAliases['alias_content_id'] = $tid;
                            $UrlAliasesDb->addOrUpdate($dataUrlAliases, ['alias_content_type' => $categoryRow->t_type, 'alias_content_id' => $tid]);
                        }
                        $CategoriesDb->rebuild([
                            'whereString' => '`t_type` = :t_type',
                            'whereValues' => [':t_type' => $categoryRow->t_type],
                        ]);
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
                    $output['redirectBack'] = $output['urls']['getCategoriesUrl'] . '?filter-t_type=' . $categoryRow->t_type;
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

            unset($data, $dataUrlAliases, $CategoriesDb, $categoryRow, $UrlAliasesDb);
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
     * Edit a category page.
     * 
     * @param int $tid The taxonomy ID.
     * @return string
     */
    public function indexAction($tid): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentCategories', ['edit']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Edit a category');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $output['tid'] = $tid;
        $output['urls'] = $this->getCategoriesUrlMethods();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        $output['baseUrl'] = $Url->getDomainProtocol() . $Url->getAppBasedPath(true);
        if (isset($_GET['t_type'])) {
            $output['t_type'] = trim($this->Input->get('t_type', '', FILTER_SANITIZE_STRING));
        } else {
            $sql = 'SELECT `tid`, `t_type` FROM `' . $this->Db->tableName('taxonomy_term_data') . '` WHERE `tid` = :tid';
            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            $Sth->bindValue('tid', $tid, \PDO::PARAM_INT);
            $Sth->execute();
            $row = $Sth->fetchObject();
            $Sth->closeCursor();
            unset($Sth);
            if (is_object($row)) {
                $output['t_type'] = $row->t_type;
            }
            unset($row);
        }

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
                'item' => d__('rdbcmsa', 'Categories'),
                'link' => $output['urls']['getCategoriesUrl'],
            ],
            [
                'item' => $output['pageTitle'],
                'link' => $output['urls']['editCategoryUrlBase'] . '/' . $tid,
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

            $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
            $FileBrowserSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\FileBrowserSubController($this->Container);// required for tinymce file browser dialog

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage'], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbcmsaCategoriesEditAction', 'rdbaHistoryState'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaCategoriesEditAction',
                'RdbCMSACategoriesIndexObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
                    'baseUrl' => $Url->getAppBasedPath(true),
                    't_type' => $output['t_type'],
                ], 
                    $this->getCategoriesUrlMethods(),
                    $FileBrowserSubController->fileBrowserGetJsObjects()// required for tinymce file browser dialog
                )
            );

            unset($ConfigDb, $FileBrowserSubController);

            // include html functions file to use `renderBreadcrumbHtml()` function.
            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Categories/edit_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
