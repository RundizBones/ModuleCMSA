<?php


namespace Rdb\Modules\RdbCMSA\Controllers\Admin;


/**
 * RundizBones CMS admin module - admin base controller.
 * 
 * Use this controller to automatically bind text domain for translation.
 */
abstract class RdbCMSAdminBaseController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        // bind text domain file and you can use translation with functions that work for specific domain such as `d__()`.
        $this->Languages->bindTextDomain(
            'rdbcmsa', 
            MODULE_PATH . DIRECTORY_SEPARATOR . 'RdbCMSA' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );
    }// __construct


}
