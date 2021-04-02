<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\_SubControllers;


/**
 * Sub controller of posts to do the common jobs between different post types.
 * 
 * This class will be here, not in admin because it must be able to call from both admin and non-admin section.
 * 
 * @since 0.0.6
 * @property-write string $categoryType Taxonomy type for category on `taxonomy_term_data`.`t_type` column. Example category, custom_category.
 * @property-write string $postType Post type on `posts`.`post_type` column.
 * @property-write string $tagType Taxonomy type for tag on `taxonomy_term_data`.`t_type` column. Example tag, custom_tag.
 */
class PostsSubController extends \Rdb\Modules\RdbAdmin\Controllers\BaseController
{


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Posts\Traits\PostsTrait;


    /**
     * @var \Rdb\Modules\RdbCMSA\Models\PostsDb $PostsDb 
     */
    public $PostsDb;


    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        // bind text domain in case it is being use by other modules.
        $this->Languages->bindTextDomain(
            'rdbcmsa', 
            MODULE_PATH . DIRECTORY_SEPARATOR . 'RdbCMSA' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );
    }// __construct


    /**
     * Magic set.
     * 
     * To allow set the protected properties on trait.
     * 
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->{$name} = $value;
        }
    }// __set


    /**
     * Delete post data on DB.
     * 
     * @param array $postIdsArray The post IDs in 2D array.
     * @return array Return associative array with keys:<br>
     *          `deleteResult` (bool) Delete result. `true` if success, `false` on failure.<br>
     *          If contain error:<br>
     *              `errorMessage`(string) The thrown exception error message with trace as string.<br>
     *              `errcatch`(bool) This will be set to `true` if exception was thrown and catched.<br>
     */
    public function deletePosts(array $postIdsArray): array
    {
        $output = [];

        try {
            $deletePostsResult = $this->PostsDb->deleteMultiple($postIdsArray);
            $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);
            $deleteUrlAliasesResult = $UrlAliasesDb->deleteMultiple($this->postType, $postIdsArray);
            unset($UrlAliasesDb);
            $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);
            $tmResult = $TranslationMatcherDb->deleteIfAllEmpty('posts', $postIdsArray);
            unset($TranslationMatcherDb);

            $deleteResult = ($deletePostsResult === true && $deleteUrlAliasesResult === true && $tmResult === true);
            unset($deletePostsResult, $deleteUrlAliasesResult, $tmResult);
        } catch (\Exception $ex) {
            $output['errorMessage'] = $ex->getMessage() . '<br>' . $ex->getTraceAsString();
            $output['errcatch'] = true;
            $deleteResult = false;
        }

        $output['deleteResult'] = $deleteResult;
        unset($deleteResult);

        return $output;
    }// deletePosts


}
