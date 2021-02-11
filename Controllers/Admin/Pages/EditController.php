<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Pages;


/**
 * Edit page controller.
 * 
 * @since 0.0.1
 */
class EditController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\PagesTrait;


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Categories\Traits\CategoriesTrait;


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Tags\Traits\TagsTrait;


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Traits\UsersTrait;


    /**
     * @var \Rdb\Modules\RdbCMSA\Models\PostsDb $PostsDb 
     */
    protected $PostsDb;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->PostsDb = new \Rdb\Modules\RdbCMSA\Models\PostsDb($Container);
        $this->PostsDb->postType = $this->postType;
        $this->PostsDb->categoryType = $this->categoryType;
        $this->PostsDb->tagType = $this->tagType;
    }// __construct


    /**
     * Do update record.
     * 
     * @global array $_PATCH
     * @param string $post_id The post ID.
     * @return string
     */
    public function doUpdateAction(string $post_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPages', ['edit']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        $output['urls'] = [];
        $output['urls'] = array_merge($output['urls'], $this->getPostsUrlsMethod());
        $output['urls'] = array_merge($output['urls'], $this->getCategoriesUrlMethods());
        $output['urls'] = array_merge($output['urls'], $this->getTagsUrlsMethod());

        // make patch data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validate csrf passed.
            $post_id = (int) $post_id;
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            // prepare data for checking.
            $data = [];
            $dataFields = [];
            $dataRevision = [];
            $dataCategories = [];
            $dataTags = [];
            $dataUrlAliases = [];
            $dataCategories = [];
            $dataTags = [];

            $PostsSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\PostsSubController($this->Container);
            $PostsSubController->categoryType = $this->categoryType;
            $PostsSubController->postType = $this->postType;
            $PostsSubController->tagType = $this->tagType;
            $PostsSubController->PostsDb = $this->PostsDb;
            $PostsSubController->populateEditFormDataOneToOne($data, $dataRevision, $dataUrlAliases);

            $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            $resultRow = $this->PostsDb->get(['posts.post_id' => $post_id]);
            if (is_object($resultRow) && !empty($resultRow)) {
                $formValidated = true;
            } else {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Not found selected post.');
                http_response_code(404);
                $formValidated = false;
            }

            if ($formValidated === true) {
                if (empty($data['post_name'])) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'Please enter the title.');
                    http_response_code(400);
                    $formValidated = false;
                }

                if ($data['post_status'] == '2' && empty($data['post_publish_date'])) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'Please enter the published date.');
                    http_response_code(400);
                    $formValidated = false;
                }
            }

            if ($formValidated === true && !empty($dataUrlAliases)) {
                // check for duplicated URL from URL aliases.
                $isDuplicated = $UrlAliasesDb->isDuplicatedUrl($dataUrlAliases['alias_url'], $dataUrlAliases['language'], $post_id, $this->postType);
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
                $PDO = $this->Db->PDO();
                $PDO->beginTransaction();

                // update using posts sub-controller.
                $outputUpdate = $PostsSubController->editUpdateData(
                    $data, 
                    $dataRevision, 
                    $dataFields, 
                    $dataCategories, 
                    $dataTags, 
                    $dataUrlAliases, 
                    $resultRow
                );
                $saveResult = ($outputUpdate['saveResult'] ?? false);
                unset($outputUpdate['saveResult']);
                $output = array_merge($output, $outputUpdate);
                unset($outputUpdate);
                // end update using posts sub-controller.

                if (isset($saveResult) && $saveResult === true) {
                    $PDO->commit();
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Updated successfully.');
                    http_response_code(200);

                    if (
                        isset($output['prog_save_command']) && 
                        $output['prog_save_command'] === 'save_stay' &&
                        isset($output['revision_id']) &&
                        $output['revision_id'] > 0
                    ) {
                        // if command is save and stay.
                    } else {
                        // anything else, save and close.
                        $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                        unset($output['formResultMessage'], $output['formResultStatus']);
                        $output['redirectBack'] = $output['urls']['getPostsUrl'];
                    }// endif save command
                } else {
                    $PDO->rollBack();
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to update.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }
                unset($PDO);
            }

            unset($data, $dataCategories, $dataFields, $dataRevision, $dataTags, $PostsSubController, $resultRow, $UrlAliasesDb);
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
     * Edit post page action.
     * 
     * @param string $post_id The post ID.
     * @return string
     */
    public function indexAction(string $post_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPages', ['edit']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Edit page');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        $output['post_id'] = (int) $post_id;
        $output['editPost'] = true;
        $output['urls'] = [];
        $output['urls'] = array_merge($output['urls'], $this->getPostsUrlsMethod());
        $output['urls'] = array_merge($output['urls'], $this->getCategoriesUrlMethods());
        $output['urls'] = array_merge($output['urls'], $this->getTagsUrlsMethod());
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
                'item' => d__('rdbcmsa', 'Pages'),
                'link' => $urlBaseWithLang . '/admin/cms/pages',
            ],
            [
                'item' => $output['pageTitle'],
                'link' => $output['urls']['editPostUrlBase'] . '/' . $post_id,
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

            $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
            $FileBrowserSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FileBrowserSubController($this->Container);// required for tinymce file browser dialog

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage', 'diff2html', 'rdbcmsaPostsEditingActions'], $Assets->mergeAssetsData('css', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addMultipleAssets('js', ['rdbcmsaPagesEditAction'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaPagesEditAction',
                'RdbCMSAPostsEditObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
                    'permissionAddTag' => $UserPermissionsDb->checkPermission('RdbCMSA', 'RdbCMSAContentTags', ['add']),
                    'tTypeForCategory' => $this->categoryType,
                    'tTypeForTag' => $this->tagType,
                    'post_id' => (int) $post_id,
                    'txtConfirmDelete' => __('Are you sure to delete?'),
                    'txtAreYouSureRollback' => d__('rdbcmsa', 'Are you sure to rollback to this item?'),
                    'txtPleaseEnterTitle' => d__('rdbcmsa', 'Please enter the title.'),
                    'txtPleaseSelectAction' => __('Please select an action.'),
                    'txtPleaseSelectAtLeastOne' => d__('rdbcmsa', 'Please select at least one item.'),
                ], 
                    $this->getPostsUrlsMethod(),
                    $this->getCategoriesUrlMethods(),
                    $this->getTagsUrlsMethod(),
                    $this->getUserUrlsMethods(),
                    $FileBrowserSubController->fileBrowserGetJsObjects()// required for tinymce file browser dialog
                )
            );

            unset($FileBrowserSubController, $UserPermissionsDb);

            // include html functions file to use `renderBreadcrumbHtml()` function.
            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Pages/add_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
