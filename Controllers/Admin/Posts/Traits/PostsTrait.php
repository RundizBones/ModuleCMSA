<?php


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Posts\Traits;


/**
 * Posts trait
 */
trait PostsTrait
{


    /**
     * @var string Taxonomy type for category on `taxonomy_term_data`.`t_type` column. Example category, custom_category.
     */
    protected $categoryType = 'category';


    /**
     * @var string Post type on `posts`.`post_type` column.
     */
    protected $postType = 'article';


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

        $output['addPostUrl'] = $urlAppBased . '/admin/cms/posts/add';
        $output['addPostRESTUrl'] = $urlAppBased . '/admin/cms/posts';
        $output['addPostRESTMethod'] = 'POST';

        $output['editPostUrlBase'] = $urlAppBased . '/admin/cms/posts/edit';
        $output['editPostRESTUrlBase'] = $urlAppBased . '/admin/cms/posts';
        $output['editPostRESTMethod'] = 'PATCH';

        $output['deletePostRESTUrlBase'] = $urlAppBased . '/admin/cms/posts';
        $output['deletePostRESTMethod'] = 'DELETE';

        $output['getPostsUrl'] = $urlAppBased . '/admin/cms/posts';
        $output['getPostsRESTUrl'] = $urlAppBased . '/admin/cms/posts';
        $output['getPostsRESTMethod'] = 'GET';

        $output['getPostRESTUrlBase'] = $urlAppBased . '/admin/cms/posts';
        $output['getPostRESTMethod'] = 'GET';

        $output['getPostFiltersRESTUrl'] = $urlAppBased . '/admin/cms/posts/filters';
        $output['getPostFiltersRESTMethod'] = 'GET';
        $output['getPostRelatedDataRESTUrl'] = $urlAppBased . '/admin/cms/posts/related-data';
        $output['getPostRelatedDataRESTMethod'] = 'GET';

        $output['getPostRevisionHistoryItemsRESTUrlBase'] = $urlAppBased . '/admin/cms/posts/revisions';
        $output['getPostRevisionHistoryItemsRESTMethod'] = 'GET';
        $output['getPostRevisionContentRESTUrl'] = $urlAppBased . '/admin/cms/posts/revisions/%post_id%/%revision_id%';
        $output['getPostRevisionContentRESTMethod'] = 'GET';
        $output['postRollbackRevisionRESTUrl'] = $urlAppBased . '/admin/cms/posts/rollback-revision/%post_id%/%revision_id%';
        $output['postRollbackRevisionRESTMethod'] = 'PUT';
        $output['deletePostRevisionItemsRESTUrl'] = $urlAppBased . '/admin/cms/posts/revisions/%post_id%';
        $output['deletePostRevisionItemsRESTMethod'] = 'DELETE';

        $output['postBulkActionsRESTUrlBase'] = $urlAppBased . '/admin/cms/posts/actions';
        $output['postBulkActionsRESTMethod'] = 'PATCH';
        $output['postBulkActionDeleteRESTUrlBase'] = $urlAppBased . '/admin/cms/posts/actions';
        $output['postBulkActionDeleteRESTMethod'] = 'DELETE';

        unset($Url, $urlAppBased);

        return $output;
    }// getPostsUrlsMethod


}
