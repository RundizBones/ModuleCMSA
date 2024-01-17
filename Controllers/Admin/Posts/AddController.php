<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Posts;


/**
 * Add post controller.
 * 
 * @since 0.0.1
 */
class AddController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\PostsTrait;


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Categories\Traits\CategoriesTrait;


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Tags\Traits\TagsTrait;


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
     * Add an item.
     * 
     * @return string
     */
    public function doAddAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPosts', ['add']);

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

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            // prepare data for checking.
            $data = [];
            $dataFields = [];
            $dataRevision = [];
            $dataCategories = [];
            $dataTags = [];
            $dataUrlAliases = [];

            $PostsSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\PostsSubController($this->Container);
            $PostsSubController->categoryType = $this->categoryType;
            $PostsSubController->postType = $this->postType;
            $PostsSubController->tagType = $this->tagType;
            $PostsSubController->populateAddFormDataOneToOne($data, $dataRevision, $dataUrlAliases);

            $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);
            $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);

            // validate the form. -------------------------------------------------------------------------
            $formValidated = false;
            if (empty($data['post_name'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Please enter the title.');
                http_response_code(400);
                $formValidated = false;
            } else {
                $formValidated = true;
            }

            if ($data['post_status'] == '2' && empty($data['post_publish_date'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Please enter the published date.');
                http_response_code(400);
                $formValidated = false;
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

            if ($formValidated === true && !empty($_POST['translation-matcher-from-post_id'])) {
                // if there is data to create new translation from source ID.
                $tmData = [];
                $tmData['fromPostId'] = $this->Input->post('translation-matcher-from-post_id', '', FILTER_SANITIZE_NUMBER_INT);
                $tmData['tmTable'] = 'posts';

                if (!is_numeric($tmData['fromPostId'])) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = d__('rdbcmsa', 'Invalid translation matcher from source ID.');
                    http_response_code(400);
                    $formValidated = false;
                }// endif; check from tid

                if ($formValidated === true) {
                    $PostsDb = new \Rdb\Modules\RdbCMSA\Models\PostsDb($this->Container);
                    $fromPostIdResult = $PostsDb->get(['posts.post_id' => intval($tmData['fromPostId'])]);
                    if (empty($fromPostIdResult) || $fromPostIdResult === false) {
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'][] = d__('rdbcmsa', 'Could not found the ID from source.');
                        http_response_code(404);
                        $formValidated = false;
                    } else {
                        $tmData['fromPostIdLanguage'] = $fromPostIdResult->language;
                    }
                    unset($fromPostIdResult, $PostsDb);
                }

                if ($formValidated === true && $TranslationMatcherDb->isCurrentLangEmpty(intval($tmData['fromPostId']), $tmData['tmTable']) === false) {
                        // if current language of selected id is not empty.
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'][] = d__('rdbcmsa', 'The translation you are trying to match is already exists.');
                        http_response_code(400);
                        $formValidated = false;
                }
            }// endif; form validated for translation matcher.
            // end validate the form. --------------------------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                $PDO = $this->Db->PDO();
                $PDO->beginTransaction();
                try {
                    $post_id = $this->PostsDb->add($data, $dataRevision);

                    if ($post_id !== false && $post_id > '0') {
                        // if add post success.
                        // populate categories and tags data.
                        $PostsSubController->populateAddFormDataOneToMany($post_id, $dataFields, $dataCategories, $dataTags);

                        $TaxonomyIndexDb = new \Rdb\Modules\RdbCMSA\Models\TaxonomyIndexDb($this->Container);
                        // add category and post index.
                        $TaxonomyIndexDb->add($dataCategories);
                        // add tag and post index.
                        $TaxonomyIndexDb->add($dataTags);

                        if (!empty($dataFields)) {
                            $PostFieldsDb = new \Rdb\Modules\RdbCMSA\Models\PostFieldsDb($this->Container);
                            $PostFieldsDb->updateMultiple($dataFields);
                            unset($PostFieldsDb);
                        }

                        if (!empty($dataUrlAliases)) {
                            $dataUrlAliases['alias_content_id'] = $post_id;
                            $UrlAliasesDb->add($dataUrlAliases);
                        }

                        unset($TaxonomyIndexDb);

                        if (isset($tmData)) {
                            // if there is translation matcher functional here.
                            // try to add or update the new id of current language with the id from previous (link clicked).
                            $output['addTranslationResult'] = $TranslationMatcherDb->addUpdateWithSource(
                                [$tmData['fromPostIdLanguage'] => $tmData['fromPostId']], 
                                [$_SERVER['RUNDIZBONES_LANGUAGE'] => $post_id], 
                                $tmData['tmTable']
                            );
                            unset($tmData);
                        }// endif; there is translation matcher data to be add.
                    }
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage();
                    $output['errcatch'] = true;
                    $post_id = false;
                }// endtry;

                if ($post_id !== false && $post_id > '0') {
                    $PDO->commit();
                    $output['post_id'] = $post_id;
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Added successfully.');
                    http_response_code(201);

                    $_SESSION['formResult'] = json_encode([($output['formResultStatus'] ?? 'success') => $output['formResultMessage']]);
                    unset($output['formResultMessage'], $output['formResultStatus']);
                    $output['redirectBack'] = $output['urls']['getPostsUrl'];
                } else {
                    $PDO->rollBack();
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = d__('rdbcmsa', 'Unable to add new post.');
                    if (isset($output['errorMessage'])) {
                        $output['formResultMessage'] .= '<br>' . $output['errorMessage'];
                    }
                    http_response_code(500);
                }
                unset($PDO);
            }

            unset($data, $dataCategories, $dataFields, $dataRevision, $dataTags, $tmData);
            unset($PostsSubController);
            unset($TranslationMatcherDb, $UrlAliasesDb);
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
     * Add post page action.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPosts', ['add']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'Add new post');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

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
                'item' => d__('rdbcmsa', 'Posts'),
                'link' => $urlBaseWithLang . '/admin/cms/posts',
            ],
            [
                'item' => $output['pageTitle'],
                'link' => $output['urls']['addPostUrl'],
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

            $Assets->addMultipleAssets('css', ['rdbcmsaPostsEditingActions'], $Assets->mergeAssetsData('css', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addMultipleAssets('js', ['rdbcmsaPostsAddAction'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaPostsAddAction',
                'RdbCMSAPostsAddObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
                    'permissionAddTag' => $UserPermissionsDb->checkPermission('RdbCMSA', 'RdbCMSAContentTags', ['add']),
                    'tTypeForCategory' => $this->categoryType,
                    'tTypeForTag' => $this->tagType,
                    'txtPleaseEnterTitle' => d__('rdbcmsa', 'Please enter the title.'),
                ], 
                    $this->getPostsUrlsMethod(),
                    $this->getCategoriesUrlMethods(),
                    $this->getTagsUrlsMethod(),
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
            $output['pageContent'] = $this->Views->render('Admin/Posts/add_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);

            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
