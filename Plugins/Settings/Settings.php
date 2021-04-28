<?php
/**
 * Name: RdbAdmin settings hook.
 * URL: https://rundiz.com
 * Version: 0.0.1
 * Description: Hooks into settings page on RdbAdmin module.
 * Author: Vee W.
 * Author URL: http://rundiz.com
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Plugins\Settings;


/**
 * RdbAdmin settings hook.
 */
class Settings implements \Rdb\Modules\RdbAdmin\Interfaces\Plugins
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * {@inheritDoc}
     */
    public function disable()
    {
    }// disable


    /**
     * {@inheritDoc}
     */
    public function enable()
    {
    }// enable


    /**
     * {@inheritDoc}
     */
    public function registerHooks()
    {
        $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
        if ($UserPermissionsDb->checkPermission('RdbCMSA', 'RdbCMSASettings', ['update']) !== true) {
            // if permission denied.
            return false;
        }
        unset($UserPermissionsDb);

        if ($this->Container->has('Plugins')) {
            /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
            $Plugins = $this->Container->get('Plugins');
            $RdbCMSASettings = new Hooks\RdbCMSASettings($this->Container);
            $Plugins->addHook('Rdb\Modules\RdbAdmin\Views\Admin\Settings\index_v.settings.tabsNav.last', [$RdbCMSASettings, 'renderTabsNav'], 10);
            $Plugins->addHook('Rdb\Modules\RdbAdmin\Views\Admin\Settings\index_v.settings.tabsContent.last', [$RdbCMSASettings, 'renderTabsContent'], 10);
            $Plugins->addHook('Rdb\Modules\RdbAdmin\Controllers\Admin\Settings\SettingsController->indexAction.afterAddAssets', [$RdbCMSASettings, 'addAssets'], 10);
            $Plugins->addHook('Rdb\Modules\RdbAdmin\Controllers\Admin\Settings\SettingsController->doUpdateAction.afterMainUpdate', [$RdbCMSASettings, 'doUpdate'], 10);
            unset($Plugins, $RdbCMSASettings);
        }
    }// registerHooks


    /**
     * {@inheritDoc}
     */
    public function uninstall()
    {
    }// uninstall


}
