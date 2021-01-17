<?php


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers;


/**
 * Sub controller of posts to do the common jobs between different post types.
 * 
 * @property-write string $categoryType Taxonomy type for category on `taxonomy_term_data`.`t_type` column. Example category, custom_category.
 * @property-write string $postType Post type on `posts`.`post_type` column.
 * @property-write string $tagType Taxonomy type for tag on `taxonomy_term_data`.`t_type` column. Example tag, custom_tag.
 */
class PostsSubController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbCMSA\Controllers\Admin\Posts\Traits\PostsTrait;


    /**
     * @var \Rdb\Modules\RdbCMSA\Models\PostsDb $PostsDb 
     */
    public $PostsDb;


    /**
     * Magic set.
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
     * Do multiple actions that is not permanently delete. (via method PATCH).
     * 
     * @param string $bulkAction The selected bulk actions input value.
     * @param array $postIdsArray The post IDs as array.
     * @param array $listPosts The result items that have got from `PostsDb->listItems()` model.
     * @return array Return associative array with these keys.<br>
     *          `debug`(array) If `APP_ENV` constant is set to development.<br>
     *          `errorMessage`(string) If failed to update.<br>
     *          `errcatch`(bool) If there is exception.<br>
     *          `saveResult`(bool) The save (update) result.
     */
    public function bulkActionsPatch(string $bulkAction, array $postIdsArray, array $listPosts): array
    {
        $PostFieldsDb = new \Rdb\Modules\RdbCMSA\Models\PostFieldsDb($this->Container);

        $trashStatusFieldName = '_rdbcmsa_posts_trash_original_status';
        $output = [];

        if (defined('APP_ENV') && APP_ENV === 'development') {
            $output['debug'] = [];
            $output['debug']['listSelectedPosts'] = $listPosts['items'];
            $output['debug']['originalPostStatus'] = [];
            $output['debug']['updateFieldStatus'] = [];
            $output['debug']['saveResult'] = [];
        }

        foreach ($listPosts['items'] as $row) {
            try {
                if ($bulkAction === 'trash') {
                    // update post_status to post fields
                    $updateFieldStatus = $PostFieldsDb->update($row->post_id, $trashStatusFieldName, $row->post_status);
                    if (defined('APP_ENV') && APP_ENV === 'development') {
                        $output['debug']['originalPostStatus'][$row->post_id] = $row->post_status;
                        $output['debug']['updateFieldStatus'][$row->post_id] = var_export($updateFieldStatus, true);
                    }
                    // DO NOT update post_status here because all of them will be 5 (trash), use update all at once below.
                } elseif ($bulkAction === 'restore') {
                    // get original status from post fields.
                    $originalPostStatus = $PostFieldsDb->get($row->post_id, $trashStatusFieldName);
                    // then delete status from post fields.
                    $updateFieldStatus = $PostFieldsDb->delete($row->post_id, $trashStatusFieldName);
                    if (defined('APP_ENV') && APP_ENV === 'development') {
                        $output['debug']['originalPostStatus'][$row->post_id] = $originalPostStatus;
                        $output['debug']['updateFieldStatus'][$row->post_id] = var_export($updateFieldStatus, true);
                    }
                    if ($updateFieldStatus === true) {
                        // then update post_status in posts table.
                        $saveResult = $this->PostsDb->update(
                            ['post_status' => ($originalPostStatus->field_value ?? 1)],
                            [],
                            [
                                'post_id' => $row->post_id,
                            ]
                        );

                        if (defined('APP_ENV') && APP_ENV === 'development') {
                            $output['debug']['saveResult'][] = var_export($saveResult, true);
                        }
                    }
                }// endif $bulkAction;

                if (!isset($updateFieldStatus) || $updateFieldStatus === false) {
                    // if update (or add) field was failed.
                    $saveResult = false;
                    break;
                }
            } catch (\Exception $ex) {
                $output['errorMessage'] = $ex->getMessage() . '<br>' . $ex->getTraceAsString();
                $output['errcatch'] = true;
                $saveResult = false;
                break;
            }
        }// endforeach;
        unset($row);

        if (isset($updateFieldStatus) && $updateFieldStatus !== false) {
            // if update (or add - not equal to false) field was succeeded.
            // update the posts status
            $data = [];
            if ($bulkAction === 'trash') {
                // if bulk action is move to trash.
                // all selected posts status will be 5 (trash).
                $data['post_status'] = 5;
                try {
                    $saveResult = $this->PostsDb->updateMultipleRows($data, $postIdsArray);
                    if (defined('APP_ENV') && APP_ENV === 'development') {
                        $output['debug']['saveResult'][] = var_export($saveResult, true);
                    }
                } catch (\Exception $ex) {
                    $output['errorMessage'] = $ex->getMessage() . '<br>' . $ex->getTraceAsString();
                    $output['errcatch'] = true;
                    $saveResult = false;
                }
            }// endif $bulkAction.

            unset($originalPostStatus, $updateFieldStatus);
        }// endif; $updateFieldStatus

        $output['saveResult'] = ($saveResult ?? false);
        unset($PostFieldsDb, $saveResult);

        return $output;
    }// bulkActionsPatch


    /**
     * Update post data to DB.
     * 
     * @param array $data The data for `posts` table.
     * @param array $dataRevision The data for `post_revision` table.
     * @param array $dataFields The data for `post_fields` table.
     * @param array $dataCategories The categories data for `taxonomy_index` table.
     * @param array $dataTags The tags data for `taxonomy_index` table.
     * @param array $dataUrlAliases The data for `url_aliases` table.
     * @param \stdClass $resultRow A post data result row of selected post ID.
     * @return array Return array with these keys.<br>
     *          `saveResult`(bool) Save result. `true` if success, `false` if failure.<br>
     *          If success:<br>
     *              `updateCategoriesResult`(array) The categories update debug data.<br>
     *              `updateTagsResult`(array) The tags update debug data.<br>
     *              `prog_save_command`(string) The input name `prog_save_command` from the form.<br>
     *              `revision_id`(int|null) The current revision ID. It can be `null` if it was not set.<br>
     *          If contains error:<br>
     *              `errorMessage`(string) The thrown exception error message with trace as string.<br>
     *              `errcatch`(bool) This will be set to `true` if exception was thrown and catched.<br>
     */
    public function editUpdateData(
        array $data, 
        array $dataRevision, 
        array $dataFields, 
        array $dataCategories, 
        array $dataTags,
        array $dataUrlAliases,
        \stdClass $resultRow
    ): array {
        $output = [];

        try {
            $output['saveResult'] = $this->PostsDb->update($data, $dataRevision, ['post_id' => $resultRow->post_id]);

            if ($output['saveResult'] === true) {
                // if update post success.
                // populate categories and tags data.
                $this->populateEditFormDataOneToMany($resultRow->post_id, $dataFields, $dataCategories, $dataTags);

                $TaxonomyIndexDb = new \Rdb\Modules\RdbCMSA\Models\TaxonomyIndexDb($this->Container);
                // update category and post index.
                $TaxonomyIndexDb->update(
                    $dataCategories, 
                    [
                        'post_id' => $resultRow->post_id, 
                        'taxonomy_term_data.t_type' => $this->categoryType,// for delete removed taxonomeis from the form.
                    ]
                );
                $output['updateCategoriesResult'] = $TaxonomyIndexDb->debugUpdate;
                // update tag and post index.
                $TaxonomyIndexDb->update(
                    $dataTags, 
                    [
                        'post_id' => $resultRow->post_id, 
                        'taxonomy_term_data.t_type' => $this->tagType,// for delete removed taxonomeis from the form.
                    ]
                );
                $output['updateTagsResult'] = $TaxonomyIndexDb->debugUpdate;

                if (!empty($dataFields)) {
                    $PostFieldsDb = new \Rdb\Modules\RdbCMSA\Models\PostFieldsDb($this->Container);
                    $PostFieldsDb->updateMultiple($dataFields);
                    unset($PostFieldsDb);
                }

                $UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);
                if (empty($dataUrlAliases)) {
                    // url alias for this maybe removed.
                    $UrlAliasesDb->delete([
                        'language' => $resultRow->language, 
                        'alias_content_type' => $resultRow->post_type, 
                        'alias_content_id' => $resultRow->post_id,
                    ]);
                } else {
                    $dataUrlAliases['alias_content_id'] = $resultRow->post_id;
                    $UrlAliasesDb->addOrUpdate(
                        $dataUrlAliases,
                        [
                            'alias_content_type' => $resultRow->post_type, 
                            'alias_content_id' => $resultRow->post_id
                        ]
                    );
                }// endif; data url aliases

                unset($TaxonomyIndexDb, $UrlAliasesDb);
            }// endif;
        } catch (\Exception $ex) {
            $output['errorMessage'] = $ex->getMessage() . '<br>' . $ex->getTraceAsString();
            $output['errcatch'] = true;
            $output['saveResult'] = false;
        }// endtry;

        if ($output['saveResult'] === true) {
            $output['prog_save_command'] = trim($this->Input->patch('prog_save_command'));
            $output['revision_id'] = ((int) $this->PostsDb->debugUpdate['revision_id'] ?? null);
        }

        return $output;
    }// editUpdateData


    /**
     * Get authors who wrote the posts.
     * 
     * This method was called from `doGetFiltersAction()` method.
     * 
     * @return array
     */
    public function getAuthorsForSelectbox(): array
    {
        $options = [];
        $options['where'] = [
            'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
            'post_type' => $this->postType,
        ];
        $posts = $this->PostsDb->listAuthors($options);
        unset($options);

        $output = [];
        $output[] = [
            'value' => '',
            'text' => d__('rdbcmsa', 'All'),
        ];

        foreach ($posts as $row) {
            $output[] = [
                'value' => $row->user_id,
                'text' => $row->user_display_name,
            ];
        }

        return $output;
    }// getAuthorsForSelectbox


    /**
     * Get categories for select box.
     * 
     * This method was called from `doGetFiltersAction()` method.
     * 
     * @return array
     */
    public function getCategoriesForSelectbox(): array
    {
        $CategoriesDb = new \Rdb\Modules\RdbCMSA\Models\CategoriesDb($this->Db->PDO(), $this->Container);
        $options = [];
        $options['unlimited'] = true;
        $options['where'] = [
            'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
            't_type' => $this->categoryType,
        ];
        $options['list_flatten'] = true;
        $categories = $CategoriesDb->listRecursive($options);
        unset($CategoriesDb, $options);

        $output = [];
        $output[] = [
            'value' => '',
            'text' => d__('rdbcmsa', 'All'),
        ];

        if (isset($categories['items']) && is_array($categories['items'])) {
            foreach ($categories['items'] as $row) {
                $output[] = [
                    'value' => $row->tid,
                    'text' => $row->t_name,
                    'level' => $row->t_level,
                    'posts' => $row->t_total,
                ];
            }// endforeach;
            unset($row);
        }

        unset($categories);
        return $output;
    }// getCategoriesForSelectbox


    /**
     * Get statuses for select box.
     * 
     * This method was called from `doGetFiltersAction()` method.
     * 
     * @return array
     */
    public function getStatusesForSelectbox(): array
    {
        $statuses = $this->PostsDb->postStatuses;

        $output = [];
        $output[] = [
            'value' => '',
            'text' => d__('rdbcmsa', 'All'),
        ];

        $statusesCount = $this->PostsDb->countStatuses([
            'post_type' => $this->postType,
            'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
        ]);

        if (is_array($statuses)) {
            foreach ($statuses as $key => $rawMsg) {
                if ($key === 6) {
                    continue;
                }

                // count posts for each status.
                $count = 0;
                foreach ($statusesCount as $statusRow) {
                    if ($statusRow->post_status == $key) {
                        $count = $statusRow->total;
                        break;
                    }
                }// endforeach;
                unset($statusRow);

                $output[] = [
                    'value' => $key,
                    'text' => d__('rdbcmsa', $rawMsg),
                    'posts' => $count,
                ];
            }// endforeach;
            unset($key, $rawMsg);
        }

        unset($statuses, $statusesCount);
        return $output;
    }// getStatusesForSelectbox


    /**
     * Populate data that receive from add page and format data structure for insert into related tables that is one-to-many relation.
     * 
     * @param int $post_id The ID from `posts` table.
     * @param array $dataFields The post_fields table.
     * @param array $dataCategories The categories field.
     * @param array $dataTags The tags field.
     */
    public function populateAddFormDataOneToMany(
        int $post_id, 
        array &$dataFields = [],
        array &$dataCategories = [], 
        array &$dataTags = []
    ) {
        // format post_fields data. --------------------------
        if (is_array($this->Input->post('post_fields', []))) {
            foreach ($this->Input->post('post_fields', []) as $index => $eachPostField) {
                $dataFields[] = [
                    'post_id' => $post_id,
                    'field_name' => $index,
                    'field_value' => $eachPostField,
                ];
            }// endforeach;
            unset($eachPostField, $index);
        }
        // end format post_fields data. ---------------------

        // format categories data. ---------------------------
        if (is_array($this->Input->post('prog_categories', []))) {
            foreach ($this->Input->post('prog_categories', []) as $eachTid) {
                $dataCategories[] = [
                    'post_id' => $post_id,
                    'tid' => $eachTid,
                ];
            }// endforeach;
            unset($eachTid);
        }
        // end format categories data. ----------------------

        // format tags data. ---------------------------------
        $progTags = json_decode($this->Input->post('prog_tags', null));
        if (is_array($progTags)) {
            $TagsDb = new \Rdb\Modules\RdbCMSA\Models\TagsDb($this->Container);
            $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
            foreach ($progTags as $eachTag) {
                if (!is_object($eachTag)) {
                    continue;
                }

                if (!property_exists($eachTag, 'tid')) {
                    // if not found tid, means newly added tag to input.
                    if ($UserPermissionsDb->checkPermission('RdbCMSA', 'RdbCMSAContentTags', ['add']) === true) {
                        // if permission is set to allowed add new tag.
                        // insert to DB and get its ID.
                        $tid = $TagsDb->add([
                            'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
                            't_type' => $this->tagType,
                            't_name' => $eachTag->value,
                        ]);

                        if ($tid === false || $tid <= '0') {
                            // failed to add new tag.
                            if ($this->Container->has('Logger')) {
                                /* @var $Logger \Rdb\System\Libraries\Logger */
                                $Logger = $this->Container->get('Logger');
                                $Logger->write('modules/cms/controllers/admin/subcontrollers/postssubcontroller', 4, 'Could not add new tag to DB. {t_name}', ['t_name' => $eachTag->value]);
                                unset($Logger);
                            }
                            continue;
                        } else {
                            // if success to add new tag.
                            $eachTag->tid = $tid;
                        }// endif failed to add new tag.
                        unset($tid);
                    } else {
                        // if permission denied to add new tag.
                        continue;
                    }// endif permission check for add new tag.
                }// endif check tid in form field.

                $dataTags[] = [
                    'post_id' => $post_id,
                    'tid' => $eachTag->tid,
                ];
            }// endforeach;
            unset($eachTag, $TagsDb, $UserPermissionsDb);
        }
        unset($progTags);
        // end format tags data. ----------------------------
    }// populateAddFormDataOneToMany


    /**
     * Populate data that receive from add page and format data structure for insert into posts and related tables that is one-to-one relation.
     * 
     * @param array $data The fields matched columns on `posts` table.
     * @param array $dataRevision The fields matched columns on `post_revision` table.
     * @param array $dataUrlAliases The fields matched columns on `url_aliases` table.
     */
    public function populateAddFormDataOneToOne(
        array &$data, 
        array &$dataRevision = [], 
        array &$dataUrlAliases = []
    ) {
        $data['user_id'] = trim($this->Input->post('user_id', $this->userSessionCookieData['user_id'], FILTER_SANITIZE_NUMBER_INT));
        $data['post_type'] = $this->postType;
        $data['language'] = ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th');
        $data['post_name'] = trim($this->Input->post('post_name', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $data['post_feature_image'] = trim($this->Input->post('post_feature_image', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $data['post_status'] = trim($this->Input->post('post_status', 1, FILTER_SANITIZE_NUMBER_INT));
        $data['post_publish_date'] = trim($this->Input->post('post_publish_date', null));
        if ($data['post_status'] != '2') {
            // if post status is not scheduled (2).
            // remove published date.
            unset($data['post_publish_date'], $data['post_publish_date_gmt']);
        }

        $dataRevision['user_id'] = $data['user_id'];
        $dataRevision['revision_head_value'] = trim($this->Input->post('revision_head_value', null));
        $dataRevision['revision_body_value'] = $this->Input->post('revision_body_value', null);
        $dataRevision['revision_body_summary'] = $this->Input->post('revision_body_summary', null);
        $dataRevision['revision_log'] = trim($this->Input->post('revision_log', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $enableRevision = trim($this->Input->post('prog_enable_revision', 0, FILTER_SANITIZE_NUMBER_INT));
        if ($enableRevision != '1') {
            // if revision checkbox is not enabled.
            // no need to keep revision log.
            $dataRevision['revision_log'] = null;
        }
        unset($enableRevision);

        // set empty string to null.
        $InputUtils = new \Rdb\Modules\RdbCMSA\Libraries\InputUtils();
        $data = $InputUtils->setEmptyScalarToNull($data);
        $dataRevision = $InputUtils->setEmptyScalarToNull($dataRevision);
        unset($InputUtils);

        if (isset($_POST['alias_url']) && !empty(trim($_POST['alias_url']))) {
            $dataUrlAliases['alias_content_type'] = $data['post_type'];
            $dataUrlAliases['language'] = $data['language'];
            $dataUrlAliases['alias_url'] = $this->Input->post('alias_url', null);
        }
    }// populateAddFormDataOneToOne


    /**
     * Populate data that receive from edit page and format data structure for insert into posts and related tables that is one-to-one relation.
     * 
     * @param array $data The fields matched columns on `posts` table.
     * @param array $dataRevision The fields matched columns on `post_revision` table.
     * @param array $dataUrlAliases The fields matched columns on `url_aliases` table.
     */
    public function populateEditFormDataOneToOne(
        array &$data, 
        array &$dataRevision = [], 
        array &$dataUrlAliases = []
    ) {
        $data['post_name'] = trim($this->Input->patch('post_name', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $data['post_feature_image'] = trim($this->Input->patch('post_feature_image', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $data['post_status'] = trim($this->Input->patch('post_status', 1, FILTER_SANITIZE_NUMBER_INT));
        $data['post_publish_date'] = trim($this->Input->patch('post_publish_date', null));
        if ($data['post_status'] != '2') {
            // if post status is not scheduled (2).
            // remove published date.
            unset($data['post_publish_date'], $data['post_publish_date_gmt']);
        }

        $dataRevision['user_id'] = trim($this->Input->patch('user_id', $this->userSessionCookieData['user_id'], FILTER_SANITIZE_NUMBER_INT));
        $dataRevision['revision_head_value'] = trim($this->Input->patch('revision_head_value', null));
        $dataRevision['revision_body_value'] = $this->Input->patch('revision_body_value', null);
        $dataRevision['revision_body_summary'] = $this->Input->patch('revision_body_summary', null);
        $dataRevision['revision_log'] = trim($this->Input->patch('revision_log', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $enableRevision = trim($this->Input->patch('prog_enable_revision', 0, FILTER_SANITIZE_NUMBER_INT));
        if ($enableRevision != '1') {
            // if revision checkbox is not enabled.
            // remove revision log to not override the existing log.
            unset($dataRevision['revision_log']);
            // remove user_id because it is for create or new revision only.
            unset($dataRevision['user_id']);
        }
        unset($enableRevision);

        // set empty string to null.
        $InputUtils = new \Rdb\Modules\RdbCMSA\Libraries\InputUtils();
        $data = $InputUtils->setEmptyScalarToNull($data);
        $dataRevision = $InputUtils->setEmptyScalarToNull($dataRevision);
        unset($InputUtils);

        global $_PATCH;
        if (isset($_PATCH['alias_url']) && !empty(trim($_PATCH['alias_url']))) {
            $dataUrlAliases['alias_content_type'] = $this->postType;
            $dataUrlAliases['language'] = ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th');
            $dataUrlAliases['alias_url'] = $this->Input->patch('alias_url', null);
        }
    }// populateEditFormDataOneToOne


    /**
     * Populate data that receive from edit page and format data structure for update/insert into taxonomy_index table.
     * 
     * @param int $post_id The ID from `posts` table.
     * @param array $dataFields The post_fields table.
     * @param array $dataCategories The categories field.
     * @param array $dataTags The tags field.
     */
    public function populateEditFormDataOneToMany(
        int $post_id, 
        array &$dataFields = [],
        array &$dataCategories = [], 
        array &$dataTags = []
    ) {
        // format post_fields data. --------------------------
        if (is_array($this->Input->patch('post_fields', []))) {
            foreach ($this->Input->patch('post_fields', []) as $index => $eachPostField) {
                $dataFields[] = [
                    'post_id' => $post_id,
                    'field_name' => $index,
                    'field_value' => $eachPostField,
                ];
            }// endforeach;
            unset($eachPostField, $index);
        }
        // end format post_fields data. ---------------------

        // format categories data. ---------------------------
        if (is_array($this->Input->patch('prog_categories', []))) {
            foreach ($this->Input->patch('prog_categories', []) as $eachTid) {
                $dataCategories[] = [
                    'post_id' => $post_id,
                    'tid' => $eachTid,
                ];
            }// endforeach;
            unset($eachTid);
        }
        // end format categories data. ----------------------

        // format tags data. ---------------------------------
        $progTags = json_decode($this->Input->patch('prog_tags', null));
        if (is_array($progTags)) {
            $TagsDb = new \Rdb\Modules\RdbCMSA\Models\TagsDb($this->Container);
            $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
            foreach ($progTags as $eachTag) {
                if (!is_object($eachTag)) {
                    continue;
                }

                if (!property_exists($eachTag, 'tid')) {
                    // if not found tid, means newly added tag to input.
                    if ($UserPermissionsDb->checkPermission('RdbCMSA', 'RdbCMSAContentTags', ['add']) === true) {
                        // if permission is set to allowed add new tag.
                        // insert to DB and get its ID.
                        $tid = $TagsDb->add([
                            'language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
                            't_type' => $this->tagType,
                            't_name' => $eachTag->value,
                        ]);

                        if ($tid === false || $tid <= '0') {
                            // failed to add new tag.
                            if ($this->Container->has('Logger')) {
                                /* @var $Logger \Rdb\System\Libraries\Logger */
                                $Logger = $this->Container->get('Logger');
                                $Logger->write('modules/cms/controllers/admin/subcontrollers/postssubcontroller', 4, 'Could not add new tag to DB. {t_name}', ['t_name' => $eachTag->value]);
                                unset($Logger);
                            }
                            continue;
                        } else {
                            // if success to add new tag.
                            $eachTag->tid = $tid;
                        }// endif failed to add new tag.
                        unset($tid);
                    } else {
                        // if permission denied to add new tag.
                        continue;
                    }// endif permission check for add new tag.
                }// endif check tid in form field.

                $dataTags[] = [
                    'post_id' => $post_id,
                    'tid' => $eachTag->tid,
                ];
            }// endforeach;
            unset($eachTag, $TagsDb, $UserPermissionsDb);
        }
        unset($progTags);
        // end format tags data. ----------------------------
    }// populateEditFormDataOneToMany


}