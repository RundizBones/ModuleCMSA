<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers;


/**
 * File browser dialog for TinyMCE sub controller.
 * 
 * This class work as easy task to implement file browser dialog with TinyMCE.
 * 
 * @since 0.0.1
 */
class FileBrowserSubController
{


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Files\Traits\FilesTrait;


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Get required JS objects to merge with existing JS objects in your controller.
     * 
     * @return array Return array that is for JS objects.
     */
    public function fileBrowserGetJsObjects(): array
    {
        if (!function_exists('d__')) {
            // if translation function is not exists, maybe it was not initialized via `RdbCMSAAdminBaseController` class.
            if ($this->Container->has('Language')) {
                /* @var $Languages \Rdb\Modules\RdbAdmin\Libraries\Languages */
                $Languages = $this->Container->get('Language');
            } else {
                $Languages = new \Rdb\Modules\RdbAdmin\Libraries\Languages($this->Container);
            }

            $Languages->bindTextDomain(
                'rdbcmsa', 
                MODULE_PATH . DIRECTORY_SEPARATOR . 'RdbCMSA' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
            );
            $Languages->getHelpers();
        }

        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [
            'publicModuleUrl' => $Url->getPublicModuleUrl(__FILE__),
            'txtFileBrowser' => d__('rdbcmsa', 'File browser'),
        ];

        // merge the urls, methods of files without prepend `urls` 
        // because this will be working with other JS object in the controllers.
        // for example: use with tinyMCE file browser, select featured image.
        // prepend `urls` can cause a mess.
        $output = array_merge(
            $output,
            $this->getFilesUrlsMethod()
        );

        unset($Url);

        return $output;
    }// fileBrowserGetJsObjects


}// FileBrowserSubController
