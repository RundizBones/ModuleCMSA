<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Settings\CMSAdmin\Traits;


/**
 * CMS admin settings trait.
 * 
 * @since 0.0.6
 */
trait SettingsCMSATrait
{


    /**
     * Get config names that will be work on settings page.
     * 
     * This method was called from `getConfigData()`, `doUpdateAction()` methods.
     * 
     * @return array
     */
    protected function getRdbCMSAConfigNames(): array
    {
        return [
            'rdbcmsa_watermarkfile',
            'rdbcmsa_watermarkAllNewUploaded',
            'rdbcmsa_watermarkPositionX',
            'rdbcmsa_watermarkPositionY',
            'rdbcmsa_watermarkPositionYPadding',
            'rdbcmsa_imageMaxDimension',
        ];
    }// getRdbCMSAConfigNames


    /**
     * Get watermark storage base path that is included this module folder name.
     * 
     * @return string Return this module folder name inside permanent folder of storage path. Example: STORAGE_PATH/permanent/Modules/RdbCMSA.
     */
    protected function getWatermarkModuleBasePath(): string
    {
        return STORAGE_PATH . DIRECTORY_SEPARATOR . 'permanent' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . 'RdbCMSA';
    }// getWatermarkModuleBase


    /**
     * Get watermark upload directory (folder) name. Just the name of folder where contains watermark file.
     * 
     * @return string
     */
    protected function getWatermarkUploadedDirectory(): string
    {
        return 'uploadedWatermark';
    }// getWatermarkUploadedDirectory


    /**
     * Get cms admin settings URLs and methods.
     * 
     * @return array
     */
    protected function getSettingsCMSAUrlsMethods(): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);
        $output = [];

        $output['getSettingsUrl'] = $urlAppBased . '/admin/settings/cms';// get settings page, also get data via rest.
        $output['getSettingsMethod'] = 'GET';

        $output['editUploadWatermarkUrl'] = $output['getSettingsUrl'];// upload watermark.
        $output['editUploadWatermarkMethod'] = 'POST';

        $output['editSettingsSubmitUrl'] = $output['getSettingsUrl'];// edit, save settings via rest.
        $output['editSettingsSubmitMethod'] = 'PATCH';

        unset($Url, $urlAppBased);
        return $output;
    }// getSettingsCMSAUrlsMethods


}
