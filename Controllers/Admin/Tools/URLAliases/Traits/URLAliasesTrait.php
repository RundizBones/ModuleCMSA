<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Tools\URLAliases\Traits;


/**
 * URL aliases trait.
 * 
 * @since 0.0.1
 */
trait URLAliasesTrait
{


    /**
     * Get URLs and methods for management controller.
     * 
     * @return array
     */
    protected function getAliasesUrlsMethod(): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);

        $output = [];

        $output['addAliasUrl'] = $urlAppBased . '/admin/tools/cms/url-aliases/add';
        $output['addAliasRESTUrl'] = $urlAppBased . '/admin/tools/cms/url-aliases';
        $output['addAliasRESTMethod'] = 'POST';

        $output['editAliasUrlBase'] = $urlAppBased . '/admin/tools/cms/url-aliases/edit';
        $output['editAliasRESTUrlBase'] = $urlAppBased . '/admin/tools/cms/url-aliases';
        $output['editAliasRESTMethod'] = 'PATCH';

        $output['deleteAliasRESTUrlBase'] = $urlAppBased . '/admin/tools/cms/url-aliases';
        $output['deleteAliasRESTMethod'] = 'DELETE';

        $output['getAliasesUrl'] = $urlAppBased . '/admin/tools/cms/url-aliases';
        $output['getAliasesRESTUrl'] = $urlAppBased . '/admin/tools/cms/url-aliases';
        $output['getAliasesRESTMethod'] = 'GET';

        $output['getAliasRESTUrlBase'] = $urlAppBased . '/admin/tools/cms/url-aliases';
        $output['getAliasRESTMethod'] = 'GET';

        unset($Url, $urlAppBased);

        return $output;
    }// getAliasesUrlsMethod


}
