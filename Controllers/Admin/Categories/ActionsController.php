<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Categories;


/**
 * Category actions controller.
 * 
 * @since 0.0.1
 */
class ActionsController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\CategoriesTrait;


    /**
     * @var \Rdb\Modules\RdbCMSA\Controllers\_SubControllers\TaxonomyTermDataSubController
     */
    protected $TaxonomyTermDataSubController;


    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->TaxonomyTermDataSubController = new \Rdb\Modules\RdbCMSA\Controllers\_SubControllers\TaxonomyTermDataSubController($Container);
        $this->TaxonomyTermDataSubController->taxonomyType = $this->taxonomyType;
    }// __construct


    /**
     * Do bulk actions.
     * 
     * @global array $_PATCH
     * @param string $tids The taxonomy IDs.
     * @return string
     */
    public function doActionsAction(string $tids): string
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
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getCategoriesUrlMethods();

        // make delete data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (!isset($_PATCH['categories-actions'])) {
            // if no action
            // don't waste time on this.
            return '';
        }

        $output['t_type'] = $this->taxonomyType;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            $bulkAction = $this->Input->patch('categories-actions');
            $tidsArray = $this->Input->patch('tid', []);
            $CategoriesDb = new \Rdb\Modules\RdbCMSA\Models\CategoriesDb($this->Db->PDO(), $this->Container);
            $TaxonomyIndexDb = new \Rdb\Modules\RdbCMSA\Models\TaxonomyIndexDb($this->Container);

            if ($bulkAction === 'recount') {
                // if action is recount (update total items).
                if (is_array($tidsArray)) {
                    if (defined('APP_ENV') && APP_ENV === 'development') {
                        $output['debug'] = [];
                        $output['debug']['update'] = [];
                    }

                    foreach ($tidsArray as $tid) {
                        $t_total = $TaxonomyIndexDb->countTaxonomy((int) $tid);
                        $updateResult = $CategoriesDb->update(['t_total' => $t_total], ['tid' => $tid]);
                        if (defined('APP_ENV') && APP_ENV === 'development') {
                            $output['debug']['update'][(int) $tid] = [
                                't_total' => $t_total,
                                'updateResult' => $updateResult,
                            ];
                        }
                        unset($t_total, $updateResult);
                    }// endforeach;
                    unset($tid);

                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Updated successfully.');
                }
            }

            unset($bulkAction, $CategoriesDb, $TaxonomyIndexDb, $tidsArray);
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
    }// doActionsAction


    /**
     * Do delete the data in db.
     * 
     * @global array $_DELETE
     * @param string $tids
     * @return string
     */
    public function doDeleteAction(string $tids): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentCategories', ['delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = $this->getCategoriesUrlMethods();

        // make delete data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (!isset($_DELETE['action'])) {
            // if no action
            // don't waste time on this.
            return '';
        }

        $output['t_type'] = $this->taxonomyType;

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            // validate category and action must be selected.
            $validateCategoryActions = $this->TaxonomyTermDataSubController->validateCategoryActions(
                $tids, 
                $_DELETE['action'], 
                ['t_type' => $output['t_type']]
            );

            if (isset($validateCategoryActions['formValidated']) && $validateCategoryActions['formValidated'] === true) {
                // if form validation passed.
                $formValidated = true;
            } else {
                // if form validation failed.
                if (isset($validateCategoryActions['formResultStatus']) && isset($validateCategoryActions['formResultMessage'])) {
                    $output['formResultStatus'] = $validateCategoryActions['formResultStatus'];
                    $output['formResultMessage'] = $validateCategoryActions['formResultMessage'];
                }
            }

            if (isset($formValidated) && $formValidated === true) {
                // if form validation passed.
                $PDO = $this->Db->PDO();
                $PDO->beginTransaction();
                $CategoriesDb = new \Rdb\Modules\RdbCMSA\Models\CategoriesDb($PDO, $this->Container);

                if (isset($validateCategoryActions['listSelectedCategories']['items']) && is_array($validateCategoryActions['listSelectedCategories']['items'])) {
                    $outputDelete = $this->TaxonomyTermDataSubController->deleteCategories($validateCategoryActions['listSelectedCategories']['items']);
                    $deleteSuccess = ($outputDelete['deleteSuccess'] ?? false);
                    unset($outputDelete['deleteSuccess']);
                    $output = array_merge($output, $outputDelete);
                    unset($outputDelete);

                    if ($deleteSuccess === true) {
                        $PDO->commit();
                        $CategoriesDb->rebuild([
                            'whereString' => 't_type = :t_type',
                            'whereValues' => [':t_type' => $output['t_type']],
                        ]);
                        $output['formResultStatus'] = 'success';
                        $output['formResultMessage'] = __('Deleted successfully.');
                        http_response_code(200);

                        $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                        unset($output['formResultMessage'], $output['formResultStatus']);
                        $output['redirectBack'] = $output['urls']['getCategoriesUrl'];
                    } else {
                        $PDO->rollBack();
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = d__('rdbcmsa', 'Unable to delete.');
                        if (isset($output['errorMessage'])) {
                            $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                        }
                        http_response_code(500);
                    }

                    unset($deleteSuccess);
                }// endif; isset tid_array

                unset($CategoriesDb, $PDO);
            }// endif; form validation passed.

            unset($formValidated, $validateCategoryActions);
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
     * Display bulk actions page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAContentCategories', ['list', 'add', 'edit', 'delete']);

        if (session_id() === '') {
            // don't set session cache because when it is in filtered category type page and click on add button, the error from get parent category will be occurs.
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = __('Confirmation required');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $output['urls'] = $this->getCategoriesUrlMethods();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);
        $output['t_type'] = $this->taxonomyType;

        // validate categories and action must be selected.
        $output = array_merge(
            $output, 
            $this->TaxonomyTermDataSubController->validateCategoryActions(
                $this->Input->get('tids'), 
                $this->Input->get('action'),
                ['t_type' => $output['t_type']]
            )
        );

        if (isset($output['action']) && $output['action'] === 'delete') {
            $this->checkPermission('RdbCMSA', 'RdbCMSAContentCategories', ['delete']);
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
                'link' => $output['urls']['getCategoriesUrl'],
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
            $Assets->addMultipleAssets('js', ['rdbcmsaCategoriesActionsAction', 'rdbaHistoryState'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaCategoriesActionsAction',
                'RdbCMSACategoriesIndexObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'baseUrl' => $Url->getAppBasedPath(true),
                ], $this->getCategoriesUrlMethods())
            );

            // include html functions file to use `renderBreadcrumbHtml()` function.
            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Categories/actions_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
