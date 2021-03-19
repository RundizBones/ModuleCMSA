<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\ModuleData;


/**
 * Module assets data for translation matcher.
 * 
 * This is separate class for easier to manage.
 */
class ModuleAssetsTranslationMatcher
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
            'js' => [
                // rdbcmsa contents translation matcher
                [
                    'handle' => 'rdbcmsaTranslationMatcherIndexAction',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/TranslationMatcher/indexAction.js',
                    'dependency' => ['rdta', 'rdbaDatatables', 'rdbaXhrDialog', 'datatables-plugins-pagination', 'rdbaCommon', 'rdbaUiXhrCommonData', 'rdbcmsaJsUtils'],
                ],
            ],
        ];
    }// getModuleAssets


}
