<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\ModuleData;


/**
 * Module assets data for post (and page).
 * 
 * This is separate class for easier to manage.
 */
class ModuleAssetsPosts
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Get module's assets list.
     * 
     * @see \Rdb\Modules\RdbAdmin\Libraries\Assets::addMultipleAssets() See <code>\Rdb\Modules\RdbAdmin\Libraries\Assets</code> class at <code>addMultipleAssets()</code> method for data structure.
     * @return array Return associative array with `css` and `js` key with its values.
     */
    public function getModuleAssets(): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        return [
            'css' => [
                // rdbcmsa contents posts
                [
                    'handle' => 'rdbcmsaPostsIndexAction',
                    'file' => $publicModuleUrl . '/assets/css/Controllers/Admin/Posts/indexAction.css',
                    'dependency' => ['rdta', 'datatables', 'rdbaCommonListDataPage'],
                ],
                [
                    'handle' => 'rdbcmsaPostsEditingActions',
                    'file' => $publicModuleUrl . '/assets/css/Controllers/Admin/Posts/editingActions.css',
                    'dependency' => ['rdta', 'tagify'],
                ],
            ],
            'js' => [
                // rdbcmsa contents posts
                [
                    'handle' => 'rdbcmsaPostsCommonActions',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Posts/commonActions.js',
                    'dependency' => [
                        'rdta', 
                        'rdbaCommon', 
                        'rdbaUiXhrCommonData', 
                        'lodash', 
                        'rdbcmsaJsUtils', 
                        'tinymce', 
                        'ace-builds', 
                        'ace-ext-keybinding_menu',
                        'ace-ext-language_tools',
                        'tagify',
                    ],
                ],
                [
                    'handle' => 'rdbcmsaPostsCommonEditRevision',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Posts/commonEditRevision.js',
                    'dependency' => [
                        'rdta', 'rdbaCommon', 
                        'rdbaDatatables', 'datatables-features-inputpaging', 
                        'jsdiff', 'diff2html', 'diff2html-ui',
                        'moment.js',
                    ],
                ],
                [
                    'handle' => 'rdbcmsaPostsIndexAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Posts/indexAction.js',
                    'dependency' => ['rdta', 'rdbaDatatables', 'rdbaXhrDialog', 'datatables-features-inputpaging', 'rdbaCommon', 'rdbaUiXhrCommonData', 'moment.js'],
                ],
                [
                    'handle' => 'rdbcmsaPostsAddAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Posts/addAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'rdbcmsaPostsCommonActions'],
                ],
                [
                    'handle' => 'rdbcmsaPostsEditAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Posts/editAction.js',
                    'dependency' => [
                        // for normal post editing.
                        'rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'rdbcmsaPostsCommonActions', 'moment.js',
                        // for revision history.
                        'rdbcmsaPostsCommonEditRevision',
                    ],
                ],
                // end rdbcmsa contents posts

                // rdbcmsa contents pages
                [
                    'handle' => 'rdbcmsaPagesIndexAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Pages/indexAction.js',
                    'dependency' => ['rdta', 'rdbaDatatables', 'rdbaXhrDialog', 'datatables-features-inputpaging', 'rdbaCommon', 'rdbaUiXhrCommonData', 'moment.js'],
                ],
                [
                    'handle' => 'rdbcmsaPagesAddAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Pages/addAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'rdbcmsaPostsCommonActions'],
                ],
                [
                    'handle' => 'rdbcmsaPagesEditAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Pages/editAction.js',
                    'dependency' => [
                        // for normal post editing.
                        'rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'rdbcmsaPostsCommonActions', 'moment.js',
                        // for revision history.
                        'rdbcmsaPostsCommonEditRevision',
                    ],
                ],
                // end rdbcmsa contents pages
            ],
        ];
    }// getModuleAssets


}
