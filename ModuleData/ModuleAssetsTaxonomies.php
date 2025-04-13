<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\ModuleData;


/**
 * Module assets data for taxonomy term data such as categories, tags.
 * 
 * This is separate class for easier to manage.
 */
class ModuleAssetsTaxonomies
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
                // rdbcmsa contents categories
                [
                    'handle' => 'rdbcmsaCategoriesIndexAction',
                    'file' => $publicModuleUrl . '/assets/css/Controllers/Admin/Categories/indexAction.css',
                    'dependency' => ['rdta'],
                ],
            ],
            'js' => [
                // rdbcmsa contents categories
                [
                    'handle' => 'rdbcmsaCategoriesIndexAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Categories/indexAction.js',
                    'dependency' => ['rdta', 'rdbaDatatables', 'rdbaXhrDialog', 'datatables-features-inputpaging', 'rdbaCommon', 'rdbaUiXhrCommonData', 'tinymce'],
                ],
                [
                    'handle' => 'rdbcmsaCategoriesCommonActions',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Categories/commonActions.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'lodash', 'rdbcmsaJsUtils', 'tinymce'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs'
                    ],
                ],
                [
                    'handle' => 'rdbcmsaCategoriesAddAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Categories/addAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'rdbcmsaCategoriesCommonActions'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs',
                    ],
                ],
                [
                    'handle' => 'rdbcmsaCategoriesEditAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Categories/editAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'rdbcmsaCategoriesCommonActions'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs',
                    ],
                ],
                [
                    'handle' => 'rdbcmsaCategoriesActionsAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Categories/actionsAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs',
                    ],
                ],
                // end rdbcmsa contents categories

                // rdbcmsa contents tags
                [
                    'handle' => 'rdbcmsaTagsIndexAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Tags/indexAction.js',
                    'dependency' => ['rdta', 'rdbaDatatables', 'rdbaXhrDialog', 'datatables-features-inputpaging', 'rdbaCommon', 'rdbaUiXhrCommonData'],
                ],
                [
                    'handle' => 'rdbcmsaTagsAddAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Tags/addAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'rdbcmsaCategoriesCommonActions'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs',
                    ],
                ],
                [
                    'handle' => 'rdbcmsaTagsEditAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Tags/editAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'rdbcmsaCategoriesCommonActions'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs',
                    ],
                ],
                // end rdbcmsa contents tags
            ],
        ];
    }// getModuleAssets


}
