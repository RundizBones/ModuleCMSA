<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Plugins\Settings\Hooks;


/**
 * RdbCMSA settings hook into RdbAdmin settings.
 * 
 * @since 0.0.6
 */
class RdbCMSASettings
{


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Settings\CMSAdmin\Traits\SettingsCMSATrait;


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var \Rdb\Modules\RdbAdmin\Libraries\Languages
     */
    protected $Languages;


    /**
     * Class constructor.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;

        $this->Languages = $this->Container['Languages'];

        // bind text domain file and you can use translation with functions that work for specific domain such as `d__()`.
        $this->Languages->bindTextDomain(
            'rdbcmsa', 
            MODULE_PATH . DIRECTORY_SEPARATOR . 'RdbCMSA' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );
    }// __construct


    /**
     * Add assets.
     * 
     * @param \Rdb\Modules\RdbAdmin\Libraries\Assets $Assets
     * @param array $rdbAdminAssets
     */
    public function addAssets(\Rdb\Modules\RdbAdmin\Libraries\Assets $Assets, array $rdbAdminAssets)
    {
        $ModuleAssets = new \Rdb\Modules\RdbCMSA\ModuleData\ModuleAssets($this->Container);
        $moduleAssetsData = $ModuleAssets->getModuleAssets();
        unset($ModuleAssets);

        $Assets->addMultipleAssets('css', ['rdbcmsaSettingsCMSA'], $moduleAssetsData);
        $Assets->addMultipleAssets('js', ['rdbcmsaSettingsCMSAHooks'], $moduleAssetsData);
        $Assets->addJsObject(
            'rdbcmsaSettingsCMSAHooks',
            'RdbCMSASettingsCMSAObject',
            [
                'urls' => $this->getSettingsCMSAUrlsMethods(),
                'txtAreYouSureDelete' => d__('rdbcmsa', 'Are you sure to delete watermark?'),
                'txtPleaseChooseOneFile' => d__('rdbcmsa', 'Please choose only one file.'),
                'txtUploading' => d__('rdbcmsa', 'Uploading'),
            ]
        );
    }// addAssets


    /**
     * Do update the settings.
     * 
     * @param array $data
     * @param bool $updateResult
     * @return bool
     */
    public function doUpdate(array $data, bool $updateResult = true)
    {
        if ($this->Container->has('Modules')) {
            /* @var $Modules \Rdb\System\Modules */
            $Modules = $this->Container->get('Modules');
            $response = $Modules->execute('\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Settings\\CMSAdmin\\Updater:doUpdate');
            unset($Modules);

            $response = json_decode($response);
            if (isset($response->updateResult) && is_bool($response->updateResult)) {
                return $response->updateResult;
            }
            unset($response);
        }
        return false;
    }// doUpdate


    /**
     * Render tabs content.
     */
    public function renderTabsContent()
    {
        include 'Views/settings_tabcontent_file_v.php';
    }// renderTabsContent


    /**
     * Render tabs navigation.
     */
    public function renderTabsNav()
    {
        include 'Views/settings_tabnav_file_v.php';
    }// renderTabsNav


}
