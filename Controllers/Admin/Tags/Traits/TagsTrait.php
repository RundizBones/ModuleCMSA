<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Tags\Traits;


/**
 * Tags trait.
 * 
 * @since 0.0.1
 */
trait TagsTrait
{


    /**
     * @var string Taxonomy type. Example: 'tag'.
     */
    protected $tagTaxonomyType = 'tag';


    /**
     * Get URLs and methods for management controller.
     * 
     * @return array
     */
    protected function getTagsUrlsMethod(): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);

        $output = [];

        $output['addTagUrl'] = $urlAppBased . '/admin/cms/tags/add';
        $output['addTagRESTUrl'] = $urlAppBased . '/admin/cms/tags';
        $output['addTagRESTMethod'] = 'POST';

        $output['editTagUrlBase'] = $urlAppBased . '/admin/cms/tags/edit';
        $output['editTagRESTUrlBase'] = $urlAppBased . '/admin/cms/tags';
        $output['editTagRESTMethod'] = 'PATCH';

        $output['bulkActionsTagRESTUrlBase'] = $urlAppBased . '/admin/cms/tags/actions';
        $output['bulkActionsTagRESTMethod'] = 'PATCH';

        $output['deleteTagRESTUrlBase'] = $urlAppBased . '/admin/cms/tags';
        $output['deleteTagRESTMethod'] = 'DELETE';

        $output['getTagsUrl'] = $urlAppBased . '/admin/cms/tags';
        $output['getTagsRESTUrl'] = $urlAppBased . '/admin/cms/tags';
        $output['getTagsRESTMethod'] = 'GET';

        $output['getTagRESTUrlBase'] = $urlAppBased . '/admin/cms/tags';
        $output['getTagRESTMethod'] = 'GET';

        // viewTagFrontUrl
        // @since 0.0.6
        $output['viewTagFrontUrl'] = $urlAppBased . '/taxonomies/%t_type%/%tid%';

        unset($Url, $urlAppBased);

        return $output;
    }// getTagsUrlsMethod


}
