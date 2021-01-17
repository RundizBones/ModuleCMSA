<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Posts;


/**
 * Editing (add, edit) controller.
 * 
 * @since 0.0.1
 */
class EditingController extends \Rdb\Modules\RdbCMSA\Controllers\Admin\RdbCMSAdminBaseController
{


    /**
     * Use `\Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait` to access method that is required for common admin pages.
     */
    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Posts\Traits\EditingTrait;


    /**
     * Get related data for editing page action.
     * 
     * Example: categories, statuses.
     * 
     * @return string
     */
    public function doGetRelatedDataAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbCMSA', 'RdbCMSAPosts', ['list', 'add', 'edit', 'delete']);

        $output = $this->getRelatedData();

        // display, response part ---------------------------------------------------------------------------------------------
        return $this->responseAcceptType($output);
    }// doGetRelatedDataAction


}
