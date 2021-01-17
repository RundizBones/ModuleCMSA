<?php


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Pages\Traits;


/**
 * Pages trait
 */
trait PagesTrait
{


    /**
     * @var string Taxonomy type for category on `taxonomy_term_data`.`t_type` column. Example category, custom_category.
     */
    protected $categoryType = 'category';


    /**
     * @var string Post type on `posts`.`post_type` column.
     */
    protected $postType = 'page';


    /**
     * @var string Taxonomy type for tag on `taxonomy_term_data`.`t_type` column. Example tag, custom_tag.
     */
    protected $tagType = 'tag';


    /**
     * Get URLs and methods for management controller.
     * 
     * @return array
     */
    protected function getPostsUrlsMethod(): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);

        $output = [];

        $output['addPostUrl'] = $urlAppBased . '/admin/cms/pages/add';
        $output['addPostRESTUrl'] = $urlAppBased . '/admin/cms/pages';
        $output['addPostRESTMethod'] = 'POST';

        $output['editPostUrlBase'] = $urlAppBased . '/admin/cms/pages/edit';
        $output['editPostRESTUrlBase'] = $urlAppBased . '/admin/cms/pages';
        $output['editPostRESTMethod'] = 'PATCH';

        $output['deletePostRESTUrlBase'] = $urlAppBased . '/admin/cms/pages';
        $output['deletePostRESTMethod'] = 'DELETE';

        $output['getPostsUrl'] = $urlAppBased . '/admin/cms/pages';
        $output['getPostsRESTUrl'] = $urlAppBased . '/admin/cms/pages';
        $output['getPostsRESTMethod'] = 'GET';

        $output['getPostRESTUrlBase'] = $urlAppBased . '/admin/cms/pages';
        $output['getPostRESTMethod'] = 'GET';

        $output['getPostFiltersRESTUrl'] = $urlAppBased . '/admin/cms/pages/filters';
        $output['getPostFiltersRESTMethod'] = 'GET';
        $output['getPostRelatedDataRESTUrl'] = $urlAppBased . '/admin/cms/pages/related-data';
        $output['getPostRelatedDataRESTMethod'] = 'GET';

        $output['getPostRevisionHistoryItemsRESTUrlBase'] = $urlAppBased . '/admin/cms/pages/revisions';
        $output['getPostRevisionHistoryItemsRESTMethod'] = 'GET';
        $output['getPostRevisionContentRESTUrl'] = $urlAppBased . '/admin/cms/pages/revisions/%post_id%/%revision_id%';
        $output['getPostRevisionContentRESTMethod'] = 'GET';
        $output['postRollbackRevisionRESTUrl'] = $urlAppBased . '/admin/cms/pages/rollback-revision/%post_id%/%revision_id%';
        $output['postRollbackRevisionRESTMethod'] = 'PUT';
        $output['deletePostRevisionItemsRESTUrl'] = $urlAppBased . '/admin/cms/pages/revisions/%post_id%';
        $output['deletePostRevisionItemsRESTMethod'] = 'DELETE';

        $output['postBulkActionsRESTUrlBase'] = $urlAppBased . '/admin/cms/pages/actions';
        $output['postBulkActionsRESTMethod'] = 'PATCH';
        $output['postBulkActionDeleteRESTUrlBase'] = $urlAppBased . '/admin/cms/pages/actions';
        $output['postBulkActionDeleteRESTMethod'] = 'DELETE';

        unset($Url, $urlAppBased);

        return $output;
    }// getPostsUrlsMethod


}
