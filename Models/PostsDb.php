<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Models;


/**
 * Posts Db.
 * 
 * @since 0.0.1
 */
class PostsDb extends \Rdb\System\Core\Models\BaseModel
{


    use Traits\CommonModelTrait;


    /**
     * @var array Allowed sort columns in db.
     */
    protected $allowedSort = ['post_id', 'parent_id', 'post_type', 'language', 'post_name', 'post_comment', 'post_status', 'post_position', 'post_add', 'post_add_gmt', 'post_update', 'post_update_gmt', 'post_publish_date', 'post_publish_date_gmt', 'alias_url'];


    /**
     * @var string Taxonomy type for category on `taxonomy_term_data`.`t_type` column. Example category, custom_category.
     */
    public $categoryType = 'category';


    /**
     * @var array Contain update debug info.
     */
    public $debugUpdate = [];


    /**
     * @var array Post status. These statuses message need to use in translation function and may filter out some status in views.
     */
    protected $postStatuses = [];


    /**
     * @var string Post type on `post_type` column for `posts` table.
     */
    public $postType = 'article';


    /**
     * @var string The `posts` table name.
     */
    protected $tableName;


    /**
     * @var string The `post_fields` table name.
     */
    protected $tableFieldsName;


    /**
     * @var string The `post_revision` table name.
     */
    protected $tableRevisionName;


    /**
     * @var string Taxonomy type for tag on `taxonomy_term_data`.`t_type` column. Example tag, custom_tag.
     */
    public $tagType = 'tag';


    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->setPostStatuses();

        $this->tableName = $this->Db->tableName('posts');
        $this->tableFieldsName = $this->Db->tableName('post_fields');
        $this->tableRevisionName = $this->Db->tableName('post_revision');
    }// __construct


    /**
     * Magic get.
     * 
     * @param string $name The property name
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        return null;
    }// __get


    /**
     * Add a post data to `posts` and `post_revision` tables.
     * 
     * @param array $data The data for `posts` table. The associative array where key is column name and value is its value.
     * @param array $dataRevision The data for `post_revision` table. The associative array where key is column name and value is its value.
     * @return mixed Return inserted ID (int) if successfully inserted, return `0` (zero integer), or `false` if failed to insert.
     */
    public function add(array $data, array $dataRevision)
    {
        // add required data if not exists.
        if (!isset($data['user_id'])) {
            $Cookie = new \Rdb\Modules\RdbAdmin\Libraries\Cookie($this->Container);
            $Cookie->setEncryption('rdbaLoggedinKey');
            $cookieData = $Cookie->get('rdbadmin_cookie_users');
            $data['user_id'] = ($cookieData['user_id'] ?? 0);
            unset($Cookie, $cookieData);
        }
        if (!isset($data['post_add'])) {
            $data['post_add'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['post_add_gmt'])) {
            $data['post_add_gmt'] = gmdate('Y-m-d H:i:s', strtotime($data['post_add']));
        }
        if (!isset($data['post_update'])) {
            $data['post_update'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['post_update_gmt'])) {
            $data['post_update_gmt'] = gmdate('Y-m-d H:i:s', strtotime($data['post_update']));
        }
        if (array_key_exists('post_status', $data) && in_array($data['post_status'], [1, 2, 4])) {
            // if post status is published (1), scheduled (2), private (4)
            if (!isset($data['post_publish_date'])) {
                if ($data['post_status'] == '2') {
                    $data['post_publish_date'] = date('Y-m-d\TH:i', strtotime('+1 hours'));
                } else {
                    $data['post_publish_date'] = date('Y-m-d\TH:i:s');
                }
            }
            if (!isset($data['post_publish_date_gmt'])) {
                $data['post_publish_date_gmt'] = gmdate('Y-m-d\TH:i:s', strtotime($data['post_publish_date']));
            }
        }
        if (!array_key_exists('user_id', $dataRevision)) {
            $dataRevision['user_id'] = $data['user_id'];
        }
        if (!isset($dataRevision['revision_date'])) {
            $dataRevision['revision_date'] = date('Y-m-d H:i:s');
        }
        if (!isset($dataRevision['revision_date_gmt'])) {
            $dataRevision['revision_date_gmt'] = gmdate('Y-m-d H:i:s', strtotime($dataRevision['revision_date']));
        }

        // add to `posts` table.
        $insertResult = $this->Db->insert($this->tableName, $data);

        if ($insertResult === true) {
            // if insert success.
            // get last id from that table.
            $post_id = $this->Db->PDO()->lastInsertId();
            $dataRevision['post_id'] = $post_id;

            // add to `post_revision` table.
            $insertRevisionResult = $this->Db->insert($this->tableRevisionName, $dataRevision);

            if ($insertRevisionResult === true) {
                $revision_id = $this->Db->PDO()->lastInsertId();
                // update `revision_id` to `posts` table.
                $this->Db->update($this->tableName, ['revision_id' => $revision_id], ['post_id' => $post_id]);

                unset($insertResult, $insertRevisionResult, $revision_id);
                return (int) $post_id;
            } else {
                return false;
            }
        }
        return false;
    }// add


    /**
     * Count all post statuses.
     * 
     * @param array $where The associative array where key is column name and value is its value.
     * @return array Return array list of each post status and its `total`.
     */
    public function countStatuses(array $where = []): array
    {
        $sql = 'SELECT `posts`.*, COUNT(*) AS total 
            FROM `' . $this->tableName . '` AS `posts`
            WHERE 1';
        $values = [];
        $placeholders = [];

        $genWhereValues = $this->Db->buildPlaceholdersAndValues($where);
        if (isset($genWhereValues['values'])) {
            $values = array_merge($values, $genWhereValues['values']);
        }
        if (isset($genWhereValues['placeholders'])) {
            $placeholders = array_merge($placeholders, $genWhereValues['placeholders']);
        }
        unset($genWhereValues);

        $sql .= ' AND ' . implode(' AND ', $placeholders);
        unset($placeholders);

        $sql .= ' GROUP BY `post_status`';

        $Sth = $this->Db->PDO()->prepare($sql);
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $sql, $value, $values);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Sth);

        if (is_array($result)) {
            return $result;
        }
        return [];
    }// countStatuses


    /**
     * Delete multiple post items.
     * 
     * It is recommended to use `PostsSubController->deletePosts()` instead of calling this method directly.<br>
     * See `\Rdb\Modules\RdbCMSA\Controllers\_SubControllers\PostsSubController::deletePosts()` for more details.
     * 
     * @param array $postIdsArray The post IDs as array.
     * @return bool Return `true` on success, `false` on failure.
     */
    public function deleteMultiple(array $postIdsArray): bool
    {
        $values = [];
        $postIdsInPlaceholder = [];
        $i = 0;
        foreach ($postIdsArray as $post_id) {
            $postIdsInPlaceholder[] = ':postIdsIn' . $i;
            $values[':postIdsIn' . $i] = $post_id;
            ++$i;
        }// endforeach;
        unset($i, $post_id);

        // delete from `posts` table.
        $sql = 'DELETE FROM `' . $this->tableName . '` WHERE `post_id` IN (' . implode(', ', $postIdsInPlaceholder) . ')';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        // bind whereValues
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $deletePostsResult = $Sth->execute();
        $Sth->closeCursor();
        unset($Sth);
        // end delete from `posts` table.

        if ($deletePostsResult === true) {
            // delete from `post_fields` table.
            $sql = 'DELETE FROM `' . $this->tableFieldsName . '` WHERE `post_id` IN (' . implode(', ', $postIdsInPlaceholder) . ')';
            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            // bind whereValues
            foreach ($values as $placeholder => $value) {
                $Sth->bindValue($placeholder, $value);
            }// endforeach;
            unset($placeholder, $value);
            $deletePostFieldsResult = $Sth->execute();
            $Sth->closeCursor();
            unset($Sth);
            // end delete from `post_fields` table.
        }

        if (isset($deletePostFieldsResult) && $deletePostFieldsResult === true) {
            // delete from `post_revision` table.
            $sql = 'DELETE FROM `' . $this->tableRevisionName . '` WHERE `post_id` IN (' . implode(', ', $postIdsInPlaceholder) . ')';
            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            // bind whereValues
            foreach ($values as $placeholder => $value) {
                $Sth->bindValue($placeholder, $value);
            }// endforeach;
            unset($placeholder, $value);
            $deletePostRevisionResult = $Sth->execute();
            $Sth->closeCursor();
            unset($Sth);
            // end delete from `post_revision` table.
        }

        unset($postIdsInPlaceholder, $values);

        if (
            isset($deletePostFieldsResult) &&
            isset($deletePostRevisionResult) &&
            isset($deletePostsResult) &&
            $deletePostFieldsResult === true &&
            $deletePostRevisionResult === true &&
            $deletePostsResult === true
        ) {
            // if successfully deleted from post related tables.
            // delete on `taxonomy_index` table.
            $TaxonomyIndexDb = new TaxonomyIndexDb($this->Container);
            return $TaxonomyIndexDb->deleteMultiple($postIdsArray);
        } else {
            return false;
        }
    }// deleteMultiple


    /**
     * Get a single post data without its parent or children (in case page).
     * 
     * @param array $where The associative array where key is column name and value is its value.
     * @param array $options The addition options. Available options:<br>
     *                          `isPublished` (bool) Set to `true` to list for published, schedule and already on time.<br>
     *                          `countRevision` (bool) Set to `true` to count total revision for this data too. This is useful for editing in admin page.<br>
     *                          `skipCategories` (bool) Skip retrieve categories or not. Default is `false` means do not skip, `true` means skip it.,<br>
     *                          `skipTags` (bool) Skip retrieve tags or not. Default is `false` means do not skip, `true` means skip it.,<br>
     *                          `skipPostFields` (bool) Skip retrieve `post_fields`table or not. Default is `true` means skip it, `false` means do not skip it.<br>
     * @return mixed Return object if result was found, return `empty`, `null`, `false` if it was not found.
     */
    public function get(array $where = [], array $options = [])
    {
        $sql = 'SELECT `posts`.*, `post_revision`.*, `url_aliases`.*, `taxonomy_index`.*,
            `users`.`user_id`, `users`.`user_login`, `users`.`user_email`, `users`.`user_display_name`,
            `posts`.`post_id` AS `post_id`, `posts`.`language` AS `language`, `posts`.`user_id` AS `user_id`
            FROM `' . $this->tableName . '` AS `posts`
            INNER JOIN `' . $this->tableRevisionName . '` AS `post_revision`
                ON `posts`.`revision_id` = `post_revision`.`revision_id`
            LEFT JOIN `' . $this->Db->tableName('url_aliases') . '` AS `url_aliases` 
                ON `posts`.`post_id` = `url_aliases`.`alias_content_id` 
                AND `posts`.`language` = `url_aliases`.`language` 
                AND `posts`.`post_type` = `url_aliases`.`alias_content_type` 
            LEFT JOIN `' . $this->Db->tableName('users') . '` AS `users`
                ON `posts`.`user_id` = `users`.`user_id`
            LEFT JOIN `' . $this->Db->tableName('taxonomy_index') . '` AS `taxonomy_index`
                ON `posts`.`post_id` = `taxonomy_index`.`post_id`
            WHERE 1';

        $values = [];
        $placeholders = [];

        if (array_key_exists('isPublished', $options) && $options['isPublished'] === true) {
            $sql .= ' AND ('
                . '`posts`.`post_status` = 1'
                . ' OR (`posts`.`post_status` = 2 AND `posts`.`post_publish_date_gmt` <= :publish_date_gmt)'
                . ' OR `posts`.`post_status` = 4'
                . ')';
            $values[':publish_date_gmt'] = gmdate('Y-m-d H:i:s');
        }

        $genWhereValues = $this->Db->buildPlaceholdersAndValues($where);
        if (isset($genWhereValues['values'])) {
            $values = array_merge($values, $genWhereValues['values']);
        }
        if (isset($genWhereValues['placeholders'])) {
            $placeholders = array_merge($placeholders, $genWhereValues['placeholders']);
        }
        unset($genWhereValues);

        $sql .= ' AND ' . implode(' AND ', $placeholders);
        unset($placeholders);
        $sql .= ' LIMIT 0, 1';

        $Sth = $this->Db->PDO()->prepare($sql);
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $sql, $value, $values);

        $Sth->execute();
        $result = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($Sth);

        if (is_object($result)) {
            if (isset($options['countRevision']) && $options['countRevision'] === true) {
                $sql = 'SELECT COUNT(*) AS `total_revisions` FROM `' . $this->tableRevisionName . '` WHERE `post_id` = :post_id AND `revision_status` = 0';
                $Sth = $this->Db->PDO()->prepare($sql);
                unset($sql);
                $Sth->bindValue(':post_id', $result->post_id);
                $Sth->execute();
                $result->total_revisions = $Sth->fetchColumn();
                $Sth->closeCursor();
                unset($Sth);
            }
            if (!empty($result->post_feature_image)) {
                $result->files = $this->getFeaturedImageDataAndURLs($result);
            }
            if (isset($options['skipPostFields']) && $options['skipPostFields'] === false) {
                // get post fields.
                $PostFieldsDb = new PostFieldsDb($this->Container);
                $postFieldsResults = $PostFieldsDb->get($result->post_id);
                $postFields = [];
                if (is_array($postFieldsResults)) {
                    foreach ($postFieldsResults as $eachField) {
                        $postFields[$eachField->field_name] = $eachField;
                    }// endforeach;
                    unset($eachField);
                }
                unset($postFieldsResults);
                $result->postFields = $postFields;
                unset($postFields);
            }
            $result->post_statusText = $this->getStatusText((int) $result->post_status);
            if (!isset($options['skipCategories']) || (isset($options['skipCategories']) && $options['skipCategories'] === false)) {
                $result->categories = $this->listRelatedTaxonomies((int) $result->post_id, $this->categoryType);
            }
            if (!isset($options['skipTags']) || (isset($options['skipTags']) && $options['skipTags'] === false)) {
                $result->tags = $this->listRelatedTaxonomies((int) $result->post_id, $this->tagType);
            }
        }

        return $result;
    }// get


    /**
     * Get URLs of featured image.
     * 
     * This method was called from `get()`.
     * 
     * @param object $result A single result row object from DB that must contain `post_feature_image` column property.
     * @return \stdClass Return new file stdClass
     */
    protected function getFeaturedImageDataAndURLs(\stdClass $result): \stdClass
    {
        $FilesDb = new FilesDb($this->Container);
        if (!empty($result->post_feature_image)) {
            $files = $FilesDb->get(['file_id' => $result->post_feature_image]);
            if (is_object($files)) {
                // if files exists.
                // format .files.urls to same structure as Controllers/Admin/Files/FileBrowser/files.js
                $files->urls = $this->getOriginalAndSmallestThumbnail($files);
            } else {
                // if file does not exists.
                $files = new \stdClass();
            }
            unset($FilesDb);
        } else {
            $files = new \stdClass();
        }

        return $files;
    }// getFeaturedImageDataAndURLs


    /**
     * Get URLs of featured image from multiple post IDs.
     * 
     * This method was called from `listItems()`.
     * 
     * @since 0.0.14
     * @param array $postIds Associative array where key is post ID and value is data in DB column `posts`.`post_feature_image`. Example:<pre>
     *              array(
     *                  2 => 15,
     *                  3 => 16,
     *              );
     * </pre>
     * @return array Return modified value of the `postIds` where its value will becomes a result row of data in DB table `files` or if not found then it will be empty object.
     */
    protected function getFeaturedImageDataAndURLsFromPosts(array $postIds): array
    {
        $FilesDb = new FilesDb($this->Container);
        $options = [
            'file_id_in' => array_values($postIds),
            'unlimited' => true,
        ];
        $result = $FilesDb->listItems($options);
        unset($options);
        unset($FilesDb);

        foreach ($postIds as $post_id => $file_id) {
            $post_id = intval($post_id);
            $foundFile = false;
            if (isset($result['items']) && is_iterable($result['items'])) {
                foreach ($result['items'] as $resultIndex => $fileRow) {
                    if (intval($fileRow->file_id) === intval($file_id)) {
                        $foundFile = true;
                        unset($result['items'][$resultIndex]);
                        break;
                    }
                }// endforeach;
                unset($resultIndex);
            }// endif; there is result from search files.

            if (true === $foundFile) {
                $postIds[$post_id] = $fileRow;
                $postIds[$post_id]->urls = $this->getOriginalAndSmallestThumbnail($fileRow);
            } else {
                $postIds[$post_id] = new \stdClass();
            }// endif; found files.
            unset($fileRow, $foundFile);
        }// endforeach;
        unset($file_id, $post_id);

        unset($result);
        return $postIds;
    }// getFeaturedImageDataAndURLsFromPosts


    /**
     * Get new post position on the `posts` table.
     * 
     * @since 0.0.6
     * @param array $where The associative array where key is column name and value is its value.
     * @return int Return the new position number (last position in DB +1), default is 1. If not found then return 1.
     */
    public function getNewPosition(array $where = []): int
    {
        $output = 1;

        $sql = 'SELECT * FROM `' . $this->tableName . '` AS `posts` WHERE 1';

        $values = [];
        $placeholders = [];

        if (!empty($where)) {
            $genWhereValues = $this->Db->buildPlaceholdersAndValues($where);
            if (isset($genWhereValues['values'])) {
                $values = array_merge($values, $genWhereValues['values']);
            }
            if (isset($genWhereValues['placeholders'])) {
                $placeholders = array_merge($placeholders, $genWhereValues['placeholders']);
            }
            unset($genWhereValues);

            $sql .= ' AND ' . implode(' AND ', $placeholders);
            unset($placeholders);
        }

        $sql .= ' ORDER BY `post_position` DESC';
        $sql .= ' LIMIT 0, 1';

        $Sth = $this->Db->PDO()->prepare($sql);
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $sql, $value, $values);

        $Sth->execute();
        $result = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($Sth);

        if (is_object($result) && !empty((array) $result)) {
            $output = (int) ($result->post_position + 1);
        }
        unset($result);

        return $output;
    }// getNewPosition


    /**
     * Get original file URL and smallest thumbnail URL.
     * 
     * @param object $files A single row result object from DB in `files` table.
     * @return \stdClass
     */
    public function getOriginalAndSmallestThumbnail(\stdClass $files): \stdClass
    {
        $FilesDb = new FilesDb($this->Container);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicUrl = $Url->getPublicUrl();

        $urls = new \stdClass();

        $urls->original = $Url->getDomainProtocol()
            . (!empty($publicUrl) ? $publicUrl : '')
            . (!empty($FilesDb->rootPublicFolderName) ? '/' . $FilesDb->rootPublicFolderName : '')
            . '/' . $FilesDb->getFileRelatePath($files);
        unset($publicUrl);

        if (
            property_exists($files, 'thumbnails') && 
            is_array($files->thumbnails) && 
            !empty($files->thumbnails)
        ) {
            foreach ($files->thumbnails as $key => $thumbnail) {
                $urls->thumbnail = $thumbnail;
                break;
            }// endforeach;
            unset($key, $thumbnail);
        } else {
            $urls->thumbnail = $urls->original;
        }

        return $urls;
    }// getOriginalAndSmallestThumbnail


    /**
     * Get post status in text (maybe translate or not depend on $translated argument).
     * 
     * @param int $post_status The post status
     * @param bool $translated Set to `true` to translate, `false` for not.
     * @return string Return readable status text if found in array key.
     */
    protected function getStatusText(int $post_status, bool $translated = true): string
    {
        $output = '';

        if (array_key_exists($post_status, $this->postStatuses)) {
            if ($translated === true) {
                $output = d__('rdbcmsa', $this->postStatuses[$post_status]);
            } else {
                $output = $this->postStatuses[$post_status];
            }
        }

        return $output;
    }// getStatusText


    /**
     * List authors who wrote post.
     * 
     * @param array $options The associative array options. Available options keys:<br>
     *                          `where` (array) the where conditions where key is column name and value is its value,<br>
     * @return array
     */
    public function listAuthors(array $options = []): array
    {
        if (!isset($options['where']['post_type'])) {
            $options['where']['post_type'] = $this->postType;
        }

        $bindValues = [];
        $sql = 'SELECT
                `posts`.`user_id` AS `user_id`,
                `users`.`user_login`,
                `users`.`user_email`,
                `users`.`user_display_name`
            FROM `' . $this->tableName . '` AS `posts`
            LEFT JOIN `' . $this->Db->tableName('users') . '` AS `users`
                ON `posts`.`user_id` = `users`.`user_id`
            WHERE 1';

        if (isset($options['where'])) {
            // where conditions.
            $placeholders = [];
            $genWhereValues = $this->Db->buildPlaceholdersAndValues($options['where']);
            if (isset($genWhereValues['values'])) {
                $bindValues = array_merge($bindValues, $genWhereValues['values']);
            }
            if (isset($genWhereValues['placeholders'])) {
                $placeholders = array_merge($placeholders, $genWhereValues['placeholders']);
            }
            unset($genWhereValues);
            $sql .= ' AND ' . implode(' AND ', $placeholders);
            unset($placeholders);
        }

        $sql .= ' GROUP BY `posts`.`user_id`';

        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();

        unset($bindValues, $Sth);
        return (is_array($result) ? $result : []);
    }// listAuthors


    /**
     * List posts.
     * 
     * @param array $options The associative array options. Available options keys:<br>
     *              `cache` (bool) Set to `true` to be able to cache the query by plugins. Default is `false`.<br>
     *              `search` (string) the search term,<br>
     *              `where` (array) the where conditions where key is column name and value is its value,<br>
     *              `tidsIn` (array) the taxonomy IDs to use in the sql command `WHERE IN (...)`<br>
     *              `postidsIn` (array) the post IDs to use in the sql command `WHERE IN (...)`<br>
     *              `postTypesIn` (array) The post types to use in the sql command `WHERE IN (...)`.<br>
     *              `isPublished` (bool) Set to `true` to list for published, schedule and already on time.<br>
     *              `sortOrders` (array) the sort order where `sort` key is column name, `order` key is mysql order (ASC, DESC),<br>
     *              `unlimited` (bool) set to `true` to show unlimited items, unset or set to `false` to show limited items,<br>
     *              `limit` (int) limit items per page. maximum is 1000,<br>
     *              `offset` (int) offset or start at record. 0 is first record,<br>
     *              `skipPostFields` (bool) Skip retrieve `post_fields`table or not. Default is `true` means skip it, `false` means do not skip it.<br>
     *              `skipCategories` (bool) Skip retrieve categories or not. Default is `false` means do not skip, `true` means skip it.,<br>
     *              `skipTags` (bool) Skip retrieve tags or not. Default is `false` means do not skip, `true` means skip it.,<br>
     * @return array Return associative array with `total` and `items` in keys.
     */
    public function listItems(array $options = []): array
    {
        if (isset($options['cache']) && true === $options['cache']) {
            // if there is an option to get/set cache.
            $cacheArgs = func_get_args();
            $cacheArgs['_progProperties'] = [
                'categoryType' => $this->categoryType,
                'postType' => $this->postType,
                'tagType' => $this->tagType,
            ];
            $cacheResult = $this->cmtGetCacheListItems($cacheArgs);

            if (!is_null($cacheResult)) {
                // if found cached result.
                unset($cacheArgs);
                return $cacheResult;
            }// endif;
        }// endif; there is cache option.

        // prepare options and check if incorrect.
        if (!isset($options['offset']) || !is_numeric($options['offset'])) {
            $options['offset'] = 0;
        }
        if (!isset($options['unlimited']) || (isset($options['unlimited']) && $options['unlimited'] !== true)) {
            if (!isset($options['limit']) || !is_numeric($options['limit'])) {
                $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
                $options['limit'] = $ConfigDb->get('rdbadmin_AdminItemsPerPage', 20);
                unset($ConfigDb);
            } elseif (isset($options['limit']) && $options['limit'] > 1000) {
                $options['limit'] = 1000;
            }
        }
        if (!isset($options['where']['post_type']) && !isset($options['postTypesIn'])) {
            $options['where']['post_type'] = $this->postType;
        }

        $bindValues = [];
        $output = [];
        $sql = 'SELECT %*% FROM `' . $this->tableName . '` AS `posts`
            INNER JOIN `' . $this->tableRevisionName . '` AS `post_revision`
                ON `posts`.`revision_id` = `post_revision`.`revision_id`
            LEFT JOIN `' . $this->tableFieldsName . '` AS `post_fields`
                ON `posts`.`post_id` = `post_fields`.`post_id`
            LEFT JOIN `' . $this->Db->tableName('url_aliases') . '` AS `url_aliases` 
                ON `posts`.`post_id` = `url_aliases`.`alias_content_id` 
                AND `posts`.`language` = `url_aliases`.`language` 
                AND `posts`.`post_type` = `url_aliases`.`alias_content_type` 
            LEFT JOIN `' . $this->Db->tableName('users') . '` AS `users`
                ON `posts`.`user_id` = `users`.`user_id`
            LEFT JOIN `' . $this->Db->tableName('taxonomy_index') . '` AS `taxonomy_index`
                ON `posts`.`post_id` = `taxonomy_index`.`post_id`
            WHERE 1';
        if (array_key_exists('search', $options) && is_scalar($options['search']) && !empty($options['search'])) {
            $sql .= ' AND (';
            $sql .= '`posts`.`post_name` LIKE :search';
            $sql .= ' OR `post_revision`.`revision_head_value` LIKE :search';
            $sql .= ' OR `post_revision`.`revision_body_value` LIKE :search';
            $sql .= ' OR `post_revision`.`revision_body_summary` LIKE :search';
            $sql .= ' OR `post_revision`.`revision_log` LIKE :search';
            $sql .= ' OR `post_fields`.`field_value` LIKE :search';
            $sql .= ')';
            $bindValues[':search'] = '%' . $options['search'] . '%';
        }

        if (isset($options['where'])) {
            // where conditions.
            $placeholders = [];
            $genWhereValues = $this->Db->buildPlaceholdersAndValues($options['where']);
            if (isset($genWhereValues['values'])) {
                $bindValues = array_merge($bindValues, $genWhereValues['values']);
            }
            if (isset($genWhereValues['placeholders'])) {
                $placeholders = array_merge($placeholders, $genWhereValues['placeholders']);
            }
            unset($genWhereValues);
            $sql .= ' AND ' . implode(' AND ', $placeholders);
            unset($placeholders);
        }

        if (array_key_exists('tidsIn', $options) && is_array($options['tidsIn']) && !empty($options['tidsIn'])) {
            // taxonomy IDs IN(..).
            $sql .= ' AND';

            $tidsInPlaceholder = [];
            $i = 0;
            foreach ($options['tidsIn'] as $tid) {
                $tidsInPlaceholder[] = ':tidsIn' . $i;
                $bindValues[':tidsIn' . $i] = $tid;
                ++$i;
            }// endforeach;
            unset($i, $tid);

            $sql .= ' `taxonomy_index`.`tid` IN (' . implode(', ', $tidsInPlaceholder) . ')';
            unset($tidsInPlaceholder);
        }

        if (array_key_exists('postidsIn', $options) && is_array($options['postidsIn']) && !empty($options['postidsIn'])) {
            // post IDs IN(..).
            $sql .= ' AND';

            $postidsInPlaceholder = [];
            $i = 0;
            foreach ($options['postidsIn'] as $post_id) {
                $postidsInPlaceholder[] = ':postidsIn' . $i;
                $bindValues[':postidsIn' . $i] = $post_id;
                ++$i;
            }// endforeach;
            unset($i, $post_id);

            $sql .= ' `posts`.`post_id` IN (' . implode(', ', $postidsInPlaceholder) . ')';
            unset($postidsInPlaceholder);
        }

        if (array_key_exists('postTypesIn', $options) && is_array($options['postTypesIn']) && !empty($options['postTypesIn'])) {
            // if there is `postTypesIn` options. (since v 0.0.14)
            // post_type IN(..).
            $sql .= ' AND';

            $posttypesInPlaceholder = [];
            $i = 0;
            foreach ($options['postTypesIn'] as $post_type) {
                $posttypesInPlaceholder[] = ':posttypesIn' . $i;
                $bindValues[':posttypesIn' . $i] = $post_type;
                ++$i;
            }// endforeach;
            unset($i, $post_type);

            $sql .= ' `posts`.`post_type` IN (' . implode(', ', $posttypesInPlaceholder) . ')';
            unset($posttypesInPlaceholder);
        }// endif;

        if (array_key_exists('isPublished', $options) && $options['isPublished'] === true) {
            $sql .= ' AND ('
                . '`posts`.`post_status` = 1'
                . ' OR (`posts`.`post_status` = 2 AND `posts`.`post_publish_date_gmt` <= :publish_date_gmt)'
                . ')';
            $bindValues[':publish_date_gmt'] = gmdate('Y-m-d H:i:s');
        }

        // prepare and get 'total' records while not set limit and offset.
        $Sth = $this->Db->PDO()->prepare(str_replace('%*%', 'COUNT(DISTINCT `posts`.`post_id`) AS `total`', $sql));
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $Sth->execute();
        $output['total'] = $Sth->fetchColumn();
        $Sth->closeCursor();
        unset($Sth);

        $sql .= ' GROUP BY `posts`.`post_id`';

        // sort and order.
        if (array_key_exists('sortOrders', $options) && is_array($options['sortOrders']) && !empty($options['sortOrders'])) {
            $orderby = [];
            foreach ($options['sortOrders'] as $sort) {
                if (
                    is_array($sort) && 
                    array_key_exists('sort', $sort) && 
                    in_array($sort['sort'], $this->allowedSort) && 
                    array_key_exists('order', $sort) && 
                    in_array(strtoupper($sort['order']), $this->allowedOrders)
                ) {
                    if ($sort['sort'] == 'alias_url') {
                        $orderby[] = '`url_aliases`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
                    } else {
                        $orderby[] = '`posts`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
                    }
                }
            }// endforeach;
            unset($sort);

            if (!empty($orderby)) {
                $sql .= ' ORDER BY ';
                $sql .= implode(', ', $orderby);
            }
            unset($orderby);
        }

        // limited or unlimited.
        if (!isset($options['unlimited']) || (isset($options['unlimited']) && $options['unlimited'] !== true)) {
            // if limited.
            $sql .= ' LIMIT ' . $options['limit'] . ' OFFSET ' . $options['offset'];
        }

        // prepare and get 'items'.
        $Sth = $this->Db->PDO()->prepare(
            str_replace(
                '%*%', 
                '`posts`.*, `post_revision`.*, `url_aliases`.*, `taxonomy_index`.*, '
                    . '`users`.`user_id`, `users`.`user_login`, `users`.`user_email`, `users`.`user_display_name`, '
                    . '`posts`.`post_id` AS `post_id`, `posts`.`language` AS `language`, `posts`.`user_id` AS `user_id`', 
                $sql
            )
        );
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($bindValues, $sql, $Sth);

        if (is_array($result) && !empty($result)) {
            $PostFieldsDb = new PostFieldsDb($this->Container);
            $filesPosts = [];
            $postIds = [];
            // loop set post IDs to retrieve all at once from tables.--------------
            foreach ($result as $key => $row) {
                if (property_exists($row, 'post_feature_image') && is_numeric($row->post_feature_image)) {
                    $filesPosts[(int) $row->post_id] = $row->post_feature_image;
                }
                $postIds[] = (int) $row->post_id;
            }// endforeach;
            unset($key, $row);
            // end loop set post IDs to retrieve all at once from tables.----------

            // retrieve data related to post all at once to save DB query. -----------
            $filesPosts = $this->getFeaturedImageDataAndURLsFromPosts($filesPosts);
            if (!isset($options['skipCategories']) || (isset($options['skipCategories']) && $options['skipCategories'] === false)) {
                // get related categories.
                $relatedCategories = $this->listRelatedTaxonomiesFromPosts($postIds, $this->categoryType);
            }// endif;
            if (!isset($options['skipTags']) || (isset($options['skipTags']) && $options['skipTags'] === false)) {
                // get related tags.
                $relatedTags = $this->listRelatedTaxonomiesFromPosts($postIds, $this->tagType);
            }
            if (isset($options['skipPostFields']) && $options['skipPostFields'] === false) {
                $postsFields = $PostFieldsDb->listPostsFields($postIds);
            }
            // end retrieve data related to post all at once to save DB query. -------
            unset($postIds);

            // loop get related categories and tags and maybe do other things.
            foreach ($result as $key => $row) {
                // get status text.
                $result[$key]->post_statusText = $this->getStatusText((int) $row->post_status);
                $result[$key]->files = ($filesPosts[intval($row->post_id)] ?? new \stdClass());

                if (isset($options['skipPostFields']) && $options['skipPostFields'] === false) {
                    // get post fields.
                    $postFieldsResults = ($postsFields[intval($row->post_id)] ?? []);
                    $postFields = [];
                    if (is_array($postFieldsResults)) {
                        foreach ($postFieldsResults as $eachField) {
                            $postFields[$eachField->field_name] = $eachField;
                        }// endforeach;
                        unset($eachField);
                    }
                    unset($postFieldsResults);
                    $result[$key]->postFields = $postFields;
                    unset($postFields);
                }

                if (!isset($options['skipCategories']) || (isset($options['skipCategories']) && $options['skipCategories'] === false)) {
                    // get related categories.
                    $result[$key]->categories = ($relatedCategories[intval($row->post_id)] ?? []);
                }

                if (!isset($options['skipTags']) || (isset($options['skipTags']) && $options['skipTags'] === false)) {
                    // get related tags.
                    $result[$key]->tags = ($relatedTags[intval($row->post_id)] ?? []);
                }
            }// endforeach;
            unset($key, $PostFieldsDb, $row);
            unset($filesPosts, $postsFields, $relatedCategories, $relatedTags);
        }// endif $result

        $output['items'] = $result;

        unset($result);

        if (isset($options['cache']) && true === $options['cache'] && isset($cacheArgs)) {
            // if there is an option to allow to set/get cache.
            $this->cmtSetCacheListItems($cacheArgs, $output);
            unset($cacheArgs);
        }// endif; there is cache option.

        return $output;
    }// listItems


    /**
     * List related taxonomies with selected post ID on specified taxonomy type.
     * 
     * This method was called from `get()`.
     * 
     * @param int $post_id The post ID.
     * @param string $t_type The type of taxonomy such as 'category', 'tag'.
     * @param array $options Additional options. @since 0.0.6.<br>
     *                  Available options:
     *                  `sortOrders` (array) the sort order where `sort` key is column name, `order` key is mysql order (ASC, DESC),<br>
     * @return array Return list of taxonomies.
     */
    protected function listRelatedTaxonomies(int $post_id, string $t_type, array $options = []): array
    {
        $sql = 'SELECT * FROM `' . $this->Db->tableName('taxonomy_index') . '` AS `taxonomy_index`
            INNER JOIN `' . $this->Db->tableName('taxonomy_term_data') . '` AS `taxonomy_term_data`
                ON `taxonomy_term_data`.`tid` = `taxonomy_index`.`tid`
            LEFT JOIN `' . $this->Db->tableName('url_aliases') . '` AS `url_aliases`
                ON `taxonomy_index`.`tid` = `url_aliases`.`alias_content_id` 
                AND `taxonomy_term_data`.`language` = `url_aliases`.`language` 
                AND `taxonomy_term_data`.`t_type` = `url_aliases`.`alias_content_type` 
            WHERE `taxonomy_index`.`post_id` = :post_id
                AND `taxonomy_term_data`.`t_type` = :t_type';

        // sort and order.
        if (array_key_exists('sortOrders', $options) && is_array($options['sortOrders']) && !empty($options['sortOrders'])) {
            $orderby = [];
            foreach ($options['sortOrders'] as $sort) {
                if (
                    is_array($sort) && 
                    array_key_exists('sort', $sort) && 
                    array_key_exists('order', $sort) && 
                    in_array(strtoupper($sort['order']), $this->allowedOrders)
                ) {
                    if (stripos($sort['sort'], '.') !== false) {
                        $orderby[] = $sort['sort'] . ' ' . strtoupper($sort['order']);
                    } else {
                        $orderby[] = '`taxonomy_index`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
                    }
                }
            }// endforeach;
            unset($sort);

            if (!empty($orderby)) {
                $sql .= ' ORDER BY ';
                $sql .= implode(', ', $orderby);
            }
            unset($orderby);
        } else {
            $sql .= ' ORDER BY `taxonomy_index`.`index_id` ASC';
        }

        $Sth = $this->Db->PDO()->prepare($sql);
        $Sth->bindValue(':post_id', $post_id);
        $Sth->bindValue(':t_type', $t_type);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($sql, $Sth);

        return (is_array($result) ? $result : []);
    }// listRelatedTaxonomies


    /**
     * List related taxonomies with selected post IDs on specified taxonomy type.
     * 
     * This will be retrieve all taxonomies from multiple post IDs at once.
     * 
     * This method was called from `listItems()`.
     * 
     * @since 0.0.14
     * @param array $postIds An indexed array contain post IDs.
     * @param string $t_type The type of taxonomy such as 'category', 'tag'.
     * @param array $options Additional options. @since 0.0.6.<br>
     *                  Available options:
     *                  `sortOrders` (array) the sort order where `sort` key is column name, `order` key is mysql order (ASC, DESC),<br>
     * @return array Return associative array where each key is `post_id` and its value is taxonomy result array and each taxonomy result array contains taxonomy row data object.
     */
    protected function listRelatedTaxonomiesFromPosts(array $postIds, string $t_type, array $options = []): array
    {
        // build bind values and placeholders. ----------------
        $bindValues = [
            ':t_type' => $t_type,
        ];
        $postIdsInPlaceholder = [];
        $i = 0;
        foreach ($postIds as $post_id) {
            $postIdsInPlaceholder[] = ':postIdsIn' . $i;
            $bindValues[':postIdsIn' . $i] = intval($post_id);
            ++$i;
        }// endforeach;
        unset($i, $post_id);
        // end build bind values and placeholders. ------------

        $sql = 'SELECT * FROM `' . $this->Db->tableName('taxonomy_index') . '` AS `taxonomy_index`
            INNER JOIN `' . $this->Db->tableName('taxonomy_term_data') . '` AS `taxonomy_term_data`
                ON `taxonomy_term_data`.`tid` = `taxonomy_index`.`tid`
            LEFT JOIN `' . $this->Db->tableName('url_aliases') . '` AS `url_aliases`
                ON `taxonomy_index`.`tid` = `url_aliases`.`alias_content_id` 
                AND `taxonomy_term_data`.`language` = `url_aliases`.`language` 
                AND `taxonomy_term_data`.`t_type` = `url_aliases`.`alias_content_type` 
            WHERE `taxonomy_index`.`post_id` IN (' . implode(', ', $postIdsInPlaceholder) . ')
                AND `taxonomy_term_data`.`t_type` = :t_type';

        // sort and order.
        if (array_key_exists('sortOrders', $options) && is_array($options['sortOrders']) && !empty($options['sortOrders'])) {
            $orderby = [];
            foreach ($options['sortOrders'] as $sort) {
                if (
                    is_array($sort) && 
                    array_key_exists('sort', $sort) && 
                    array_key_exists('order', $sort) && 
                    in_array(strtoupper($sort['order']), $this->allowedOrders)
                ) {
                    if (stripos($sort['sort'], '.') !== false) {
                        $orderby[] = $sort['sort'] . ' ' . strtoupper($sort['order']);
                    } else {
                        $orderby[] = '`taxonomy_index`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
                    }
                }
            }// endforeach;
            unset($sort);

            if (!empty($orderby)) {
                $sql .= ' ORDER BY ';
                $sql .= implode(', ', $orderby);
            }
            unset($orderby);
        } else {
            $sql .= ' ORDER BY `taxonomy_index`.`index_id` ASC';
        }

        $Sth = $this->Db->PDO()->prepare($sql);
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $postIdsInPlaceholder, $value);
        // end bind values
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($bindValues, $sql, $Sth);

        if (is_iterable($result)) {
            $newResult = [];
            foreach ($postIds as $post_id) {
                $post_id = intval($post_id);
                $found = false;
                $tmpTaxonomyResultForAPost = [];
                foreach ($result as $resultIndex => $row) {
                    if (intval($row->post_id) === $post_id) {
                        $tmpTaxonomyResultForAPost[] = $row;
                        unset($result[$resultIndex]);
                        $found = true;
                    }
                }// endforeach;
                unset($resultIndex);

                if (true === $found) {
                    $newResult[$post_id] = $tmpTaxonomyResultForAPost;
                } else {
                    $newResult[$post_id] = [];
                }
                unset($found, $tmpTaxonomyResultForAPost);
            }// endforeach; post IDs.
            unset($post_id, $row);
        }// endif;
        //unset($result);

        return ($newResult ?? []);
    }// listRelatedTaxonomiesFromPosts


    /**
     * Set all post statuses.
     * 
     * This method was called from `__construct()` method.
     */
    private function setPostStatuses()
    {
        $this->postStatuses = [
            0 => noop__('Draft'),
            1 => noop__('Published'),
            2 => noop__('Scheduled'),
            3 => noop__('Pending'),
            4 => noop__('Private'),
            5 => noop__('Trash'),
            6 => noop__('Inherit'),
        ];
    }// setPostStatuses


    /**
     * Update a post data to `posts` and `post_revision` tables.
     * 
     * @param array $data The data for `posts` table. The associative array where key is column name and value is its value.
     * @param array $dataRevision The data for `post_revision` table. The associative array where key is column name and value is its value.
     * @param array $where The associative array where its key is column name and value is its value.
     * @return bool Return `true` on success update, `false` for otherwise.
     * @throws \InvalidArgumentException if required array key is missed.
     */
    public function update(array $data, array $dataRevision, array $where): bool
    {
        if (!array_key_exists('post_id', $where)) {
            // if post_id is not found in $where.
            throw new \InvalidArgumentException('The $where argument required `post_id` array key to properly update the data.');
        }

        // add required data and remove unnecessary data.
        $newRevision = false;
        $this->updatePrepareData($data, $dataRevision, $newRevision);

        // update to `posts` table.
        $updateResult = $this->Db->update($this->tableName, $data, $where);
        $this->debugUpdate['updateResult'] = $updateResult;

        if ($updateResult === true) {
            // if update success.
            if (!empty($dataRevision)) {
                // if data revision is not empty.
                if (isset($newRevision) && $newRevision === true) {
                    // if use add new revision.
                    $dataRevision['post_id'] = (int) $where['post_id'];
                    $insertRevisionResult = $this->Db->insert($this->tableRevisionName, $dataRevision);
                    $this->debugUpdate['insertRevisionResult'] = $insertRevisionResult;
                    if ($insertRevisionResult === true) {
                        $revision_id = $this->Db->PDO()->lastInsertId();
                        $this->debugUpdate['revision_id'] = (int) $revision_id;
                        return $this->Db->update($this->tableName, ['revision_id' => $revision_id], $where);
                    }
                    unset($insertRevisionResult);
                    return false;
                } else {
                    // if use update current revision.
                    $result = $this->get(['posts.post_id' => $where['post_id']]);
                    if (is_object($result) && !empty($result)) {
                        $this->debugUpdate['revision_id'] = (int) $result->revision_id;
                        return $this->Db->update(
                            $this->tableRevisionName, 
                            $dataRevision, 
                            [
                                'revision_id' => $result->revision_id,
                                'post_id' => $where['post_id'],
                            ]
                        );
                    }
                    unset($result);
                    return false;
                }// endif $newRevision
            } else {
                // if data revision is empty.
                // just finished.
                return true;
            }// endif $dataRevision is not empty.
        }
        return false;
    }// update


    /**
     * Update multiple rows for `posts` table only.
     * 
     * @param array $data The data for `posts` table. The associative array where key is column name and value is its value.
     * @param array $post_ids The post IDs to update.
     * @return bool Return `true` on success update, `false` for otherwise.
     */
    public function updateMultipleRows(array $data, array $post_ids): bool
    {
        $dataRevision = [];
        $newRevision = false;
        $this->updatePrepareData($data, $dataRevision, $newRevision);

        $values = [];
        $sets = [];

        $genData = $this->Db->buildPlaceholdersAndValues($data, false);
        if (isset($genData['values'])) {
            $values = array_merge($values, $genData['values']);
        }
        if (isset($genData['placeholders'])) {
            $sets = array_merge($sets, $genData['placeholders']);
        }
        unset($genData);

        $postIdsInPlaceholder = [];
        $i = 0;
        foreach ($post_ids as $post_id) {
            $postIdsInPlaceholder[] = ':postIdsIn' . $i;
            $values[':postIdsIn' . $i] = $post_id;
            ++$i;
        }// endforeach;
        unset($i, $post_id);

        $sql = 'UPDATE `' . $this->tableName . '` SET ' . implode(', ', $sets) . ' WHERE `post_id`IN (' . implode(', ', $postIdsInPlaceholder) . ')';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($postIdsInPlaceholder, $sets, $sql);
        // bind whereValues
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value, $values);

        return $Sth->execute();
    }// updateMultipleRows


    /**
     * Prepare data for update process by add required data AND remove unnecessary data.
     * 
     * @param array $data The data for `posts` table. The associative array where key is column name and value is its value.
     * @param array $dataRevision The data for `post_revision` table.
     * @param bool $newRevision Determine that is this really should be new revision even if new revision was checked.
     */
    protected function updatePrepareData(array &$data, array &$dataRevision, bool &$newRevision)
    {
        // add required data if not exists.
        if (!isset($data['post_update'])) {
            $data['post_update'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['post_update_gmt'])) {
            $data['post_update_gmt'] = gmdate('Y-m-d H:i:s', strtotime($data['post_update']));
        }
        // the publish date will be prepared on `PostsSubController->editUpdateData()` method.
        if (isset($dataRevision['user_id'])) {
            $newRevision = true;
            if (!isset($dataRevision['revision_date'])) {
                $dataRevision['revision_date'] = date('Y-m-d H:i:s');
            }
            if (!isset($dataRevision['revision_date_gmt'])) {
                $dataRevision['revision_date_gmt'] = gmdate('Y-m-d H:i:s', strtotime($dataRevision['revision_date']));
            }
        }
        // end add required data.

        // remove unnecessary data.
        unset(
            $data['user_id'],
            $data['post_type'],
            $data['language'],
            $data['post_add'],
            $data['post_add_gmt']
        );
    }// updatePrepareData


}
