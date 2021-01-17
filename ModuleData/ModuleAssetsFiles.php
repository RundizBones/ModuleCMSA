<?php


namespace Rdb\Modules\RdbCMSA\ModuleData;


/**
 * Module assets data for file.
 * 
 * This is separate class for easier to manage.
 */
class ModuleAssetsFiles
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
                // rdbcmsa contents files
                [
                    'handle' => 'rdbcmsaFilesIndexAction',
                    'file' => $publicModuleUrl . '/assets/css/Controllers/Admin/Files/indexAction.css',
                    'dependency' => ['rdta'],
                ],
                [
                    'handle' => 'rdbcmsaFilesFileBrowserAction',
                    'file' => $publicModuleUrl . '/assets/css/Controllers/Admin/Files/fileBrowserAction.css',
                    'dependency' => ['rdta'],
                ],
                [
                    'handle' => 'rdbcmsaFilesEditAction',
                    'file' => $publicModuleUrl . '/assets/css/Controllers/Admin/Files/editAction.css',
                    'dependency' => ['rdta'],
                    'attributes' => [
                        'class' => 'ajaxInjectCss'
                    ],
                ],
            ],
            'js' => [
                // rdbcmsa contents files
                [
                    'handle' => 'rdbcmsaFilesCommonActions',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Files/commonActions.js',
                    'dependency' => ['rdta', 'rdbaCommon'],
                ],
                [
                    'handle' => 'rdbcmsaFilesIndexActionFolders',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Files/indexActionFolders.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbcmsaFilesCommonActions'],
                ],
                [
                    'handle' => 'rdbcmsaFilesIndexActionFiles',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Files/indexActionFiles.js',
                    'dependency' => ['rdta', 'rdbaDatatables', 'datatables-plugins-pagination', 'rdbaXhrDialog', 'rdbaCommon', 'rdbaUiXhrCommonData', 'rdbcmsaFilesCommonActions', 'moment.js'],
                ],
                [
                    'handle' => 'rdbcmsaFilesEditAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Files/editAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'rdbcmsaCategoriesCommonActions'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs'
                    ],
                ],
                [
                    'handle' => 'rdbcmsaFilesActionsAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Files/actionsAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs',
                    ],
                ],
                [
                    'handle' => 'rdbcmsaFilesFileBrowserFolders',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Files/FileBrowser/folders.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbcmsaFilesCommonActions'],
                ],
                [
                    'handle' => 'rdbcmsaFilesFileBrowserFiles',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Files/FileBrowser/files.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbcmsaFilesCommonActions'],
                ],
                // end rdbcmsa contents files
            ],
        ];
    }// getModuleAssets


}
