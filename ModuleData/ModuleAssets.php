<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\ModuleData;


/**
 * Module assets data.
 */
class ModuleAssets
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

        $ModuleAssetsFiles = new ModuleAssetsFiles($this->Container);
        $ModuleAssetsPosts = new ModuleAssetsPosts($this->Container);
        $ModuleAssetsTaxonomies = new ModuleAssetsTaxonomies($this->Container);
        $ModuleAssetsUrlAliases = new ModuleAssetsUrlAliases($this->Container);
        $ModuleAssetsEncodeDecode = new ModuleAssetsEncodeDecode($this->Container);

        return array_merge_recursive(
            [
                'css' => [
                    [
                        'handle' => 'tagify',
                        'file' => $publicModuleUrl . '/assets/vendor/tagify/tagify.css',
                        'version' => '3.22.1',
                    ],
                    [
                        'handle' => 'diff2html',
                        'file' => $publicModuleUrl . '/assets/vendor/diff2html/css/diff2html.min.css',
                        'version' => '3.2.0',
                    ],
                ],
                'js' => [
                    // ace js
                    [
                        'handle' => 'ace-builds',
                        'file' => $publicModuleUrl . '/assets/vendor/ace-builds/ace.js',
                        'version' => '1.4.12',
                    ],
                    [
                        'handle' => 'ace-ext-modelist',
                        'file' => $publicModuleUrl . '/assets/vendor/ace-builds/ext-modelist.js',
                        'version' => '1.4.12',
                    ],
                    // diff, diff2html
                    [
                        'handle' => 'jsdiff',
                        'file' => $publicModuleUrl . '/assets/vendor/diff/diff.js',
                        'version' => '5.0.0',
                    ],
                    [
                        'handle' => 'diff2html',
                        'file' => $publicModuleUrl . '/assets/vendor/diff2html/js/diff2html.min.js',
                        'dependency' => ['jsdiff'],
                        'version' => '3.2.0',
                    ],
                    [
                        'handle' => 'diff2html-ui',
                        'file' => $publicModuleUrl . '/assets/vendor/diff2html/js/diff2html-ui-slim.min.js',
                        'dependency' => ['diff2html'],
                        'version' => '3.2.0',
                    ],
                    // end diff, diff2html
                    [
                        'handle' => 'tagify',
                        'file' => $publicModuleUrl . '/assets/vendor/tagify/tagify.min.js',
                        'version' => '3.22.1',
                    ],
                    [
                        'handle' => 'tinymce',
                        'file' => $publicModuleUrl . '/assets/vendor/tinymce/tinymce.min.js',
                        'dependency' => ['rdta'],
                        'version' => '5.5.1',
                    ],
                    // js utilities class for this module.
                    [
                        'handle' => 'rdbcmsaJsUtils',
                        'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/RdsUtils.js',
                        'attributes' => [
                            'class' => 'ajaxInjectJs'// this is required because js/Controllers/Admin/Categories/commonActions.js use it on ajax dialog page.
                        ],
                    ],
                ],// end js array
            ],// end return array
            $ModuleAssetsFiles->getModuleAssets(),
            $ModuleAssetsPosts->getModuleAssets(),
            $ModuleAssetsTaxonomies->getModuleAssets(),
            $ModuleAssetsUrlAliases->getModuleAssets(),
            $ModuleAssetsEncodeDecode->getModuleAssets()
        );// end array_merge_recursive
    }// getModuleAssets


}
