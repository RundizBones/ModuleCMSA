<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Settings\CMSAdmin;


/**
 * Settings for CMS admin.
 * 
 * @since 0.0.6
 */
class IndexController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\SettingsCMSATrait;


    /**
     * Get config (settings) values.
     * 
     * @return array
     */
    protected function getConfigData(): array
    {
        $configNames = $this->getRdbCMSAConfigNames();

        // get data from db not model to get it fresh (without cache).
        $placeholders = array_fill(0, (int) count($configNames), '?');
        $sql = 'SELECT * FROM `' . $this->Db->tableName('config') . '` WHERE `config_name` IN (' . implode(', ', $placeholders) . ')';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($placeholders, $sql);
        $Sth->execute($configNames);
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($configNames, $Sth);

        if (is_array($result)) {
            return $result;
        }
        return [];
    }// getConfigData


    /**
     * Display settings page, REST API data.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSASettings', ['update']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        // urls & methods
        $output['urls'] = $this->getSettingsCMSAUrlsMethods();

        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if request via REST API or AJAX
            $output['configData'] = $this->getConfigData();
            if (!empty($output['configData']) && is_array($output['configData'])) {
                foreach ($output['configData'] as $index => $item) {
                    if ($item->config_name === 'rdbcmsa_watermarkfile') {
                        $filePath = $this->getWatermarkModuleBasePath() . DIRECTORY_SEPARATOR . $item->config_value;
                        $filePathExp = explode('/', str_replace(['\\', '/', DIRECTORY_SEPARATOR], '/', $filePath));
                        $fileWithExt = $filePathExp[(count($filePathExp) - 1)];
                        unset($filePathExp[(count($filePathExp) - 1)]);
                        $fileParentDir = implode(DIRECTORY_SEPARATOR, $filePathExp);
                        unset($filePathExp);
                        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem($fileParentDir);
                        $output['rdbcmsa_watermarkfile_base64'] = $FileSystem->getBase64File($fileWithExt);
                        unset($FileSystem, $fileParentDir, $filePath, $fileWithExt);
                        break;
                    }
                }// endforeach;
                unset($index, $item);
            }
        }

        $output['pageTitle'] = d__('rdbcmsa', 'CMS admin settings');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom HTTP accept, response content.
            // response the data.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            // get module's assets
            $ModuleAssets = new \Rdb\Modules\RdbCMSA\ModuleData\ModuleAssets($this->Container);
            $moduleAssetsData = $ModuleAssets->getModuleAssets();
            unset($ModuleAssets);
            // Assets class for add CSS and JS.
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['rdbcmsaSettingsCMSA'], $Assets->mergeAssetsData('css', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addMultipleAssets('js', ['rdbcmsaSettingsCMSA'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaSettingsCMSA',
                'RdbCMSASettingsCMSAObject',
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'urls' => $output['urls'],
                    'txtAreYouSureDelete' => d__('rdbcmsa', 'Are you sure to delete watermark?'),
                    'txtPleaseChooseOneFile' => d__('rdbcmsa', 'Please choose only one file.'),
                    'txtUploading' => d__('rdbcmsa', 'Uploading'),
                ]
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Settings/CMSAdmin/index_v', $output);

            unset($Assets, $moduleAssetsData, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
