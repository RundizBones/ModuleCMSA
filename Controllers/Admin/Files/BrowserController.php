<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Files;


/**
 * File browser (in dialog) controller.
 * 
 * @since 0.0.1
 */
class BrowserController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Traits\UsersTrait;


    use Traits\FilesTrait;


    /**
     * Listing page action.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAFiles', ['list', 'add', 'edit', 'delete']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf([
            'persistentTokenMode' => true,
        ]);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output['pageTitle'] = d__('rdbcmsa', 'File browser');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();
        $output['rootPublicFolderName'] = $this->rootPublicFolderName;

        $output = array_merge($output, $Csrf->createToken());

        unset($Csrf);

        $output['urls'] = $this->getFilesUrlsMethod();
        $output = array_merge($output, $this->getUserUrlsMethods());

        $output['domainProtocol'] = $Url->getDomainProtocol();
        $output['publicUrl'] = $Url->getPublicUrl();
        $output['rootPublicFolderName'] = $this->rootPublicFolderName;
        $output['fullUrlToRootPublicStorage'] = $output['domainProtocol'] . 
            (!empty($output['publicUrl']) ? $output['publicUrl'] : '') . 
            (!empty($output['rootPublicFolderName']) ? '/' . $output['rootPublicFolderName'] : '');
        $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\FilesSubController();
        $output['thumbnailSizes'] = $FilesSubController->getThumbnailSizes();
        $output['fullUrlToRoot'] = $output['domainProtocol'] . 
            (!empty($output['publicUrl']) ? $output['publicUrl'] : '');

        if ($this->Input->get('featured-image') === '1') {
            $output['featuredImage'] = true;
        }
        if ($this->Input->get('select-images') === '1') {
            $output['selectImages'] = true;
        }
        if (!empty(trim($this->Input->get('set-button-message')))) {
            $output['setButtonMessage'] = $this->Input->get('set-button-message');
        }

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            // get RdbAdmin module's assets data for render page correctly.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            // get module's assets
            $ModuleAssets = new \Rdb\Modules\RdbCMSA\ModuleData\ModuleAssets($this->Container);
            $moduleAssetsData = $ModuleAssets->getModuleAssets();
            unset($ModuleAssets);
            // Assets class for add CSS and JS.
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            // add CSS and JS assets to make basic functional and style on admin page works correctly.
            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            $_SERVER['RUNDIZBONES_SUBREQUEST'] = true;

            $Assets->addMultipleAssets('css', ['rdbcmsaFilesFileBrowserAction'], $Assets->mergeAssetsData('css', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addMultipleAssets('js', ['rdbcmsaFilesFileBrowserFolders', 'rdbcmsaFilesFileBrowserFiles'], $Assets->mergeAssetsData('js', $moduleAssetsData, $rdbAdminAssets));
            $Assets->addJsObject(
                'rdbcmsaFilesCommonActions',
                'RdbCMSAFilesCommonObject',
                array_merge([
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'audioExtensions' => $FilesSubController->audioExtensions,
                    'imageExtensions' => $FilesSubController->imageExtensions,
                    'videoExtensions' => $FilesSubController->videoExtensions,
                    'thumbnailSizes' => $FilesSubController->getThumbnailSizes(),
                    'rootPublicUrl' => $Url->getPublicUrl(),
                    'rootPublicFolderName' => $output['rootPublicFolderName'],
                    'domainProtocol' => $output['domainProtocol'],
                    'fullUrlToRootPublicStorage' => $output['fullUrlToRootPublicStorage'],
                    'fullUrlToRoot' => $output['fullUrlToRoot'],
                    'featuredImage' => ($output['featuredImage'] ?? false),
                    'selectImages' => ($output['selectImages'] ?? false),
                    'txtPleaseSelectAtLeastOne' => d__('rdbcmsa', 'Please select at least one item.'),
                ], 
                    $this->getFilesUrlsMethod(),
                    $this->getUserUrlsMethods()
                )
            );

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Files/browser_v', $output);

            unset($Assets, $FilesSubController, $rdbAdminAssets, $Url);

            return $this->Views->render('common/Admin/emptyLayout_v', $output, ['viewsModule' => 'RdbAdmin']);
        }
    }// indexAction


}
