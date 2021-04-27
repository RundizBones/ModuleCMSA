<?php


namespace Rdb\Modules\RdbCMSA\ModuleData;


/**
 * Module assets data for settings page.
 * 
 * This is separate class for easier to manage.
 * 
 * @since 0.0.6
 */
class ModuleAssetsSettings
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
                // cms admin settings
                [
                    'handle' => 'rdbcmsaSettingsCMSA',
                    'file' => $publicModuleUrl . '/assets/css/Controllers/Admin/Settings/CMSAdmin/indexAction.css',
                    'dependency' => ['rdta'],
                ],
            ],
            'js' => [
                // cms admin settings
                [
                    'handle' => 'rdbcmsaSettingsCMSA',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Settings/CMSAdmin/indexAction.js',
                    'dependency' => ['rdta'],
                ],
            ],
        ];
    }// getModuleAssets


}
