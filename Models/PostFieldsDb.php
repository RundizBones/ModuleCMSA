<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Models;


/**
 * Post fields DB model.
 * 
 * @since 0.0.1
 */
class PostFieldsDb extends \Rdb\System\Core\Models\BaseModel
{


    use \Rdb\Modules\RdbAdmin\Models\Traits\MetaFieldsTrait;


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->storagePath = STORAGE_PATH . '/cache/Modules/RdbCMSA/Models/post_fields';
        $this->tableName = $this->Db->tableName('post_fields');
        $this->objectIdName = 'post_id';
        $this->beginMetaFieldsTrait($Container);
    }// __construct


    /**
     * Delete post field.
     * 
     * @param int $post_id The post ID.
     * @param string $field_name Field name.
     * @return bool Return `true` on success, `false` for otherwise.
     */
    public function delete(int $post_id, string $field_name): bool
    {
        if (empty($field_name)) {
            return false;
        }

        return $this->deleteFieldsData($post_id, $field_name);
    }// delete


    /**
     * Delete all fields for specific post ID.
     * 
     * @param int $post_id The post ID.
     * @return bool Return `true` on success, `false` for otherwise.
     */
    public function deleteAllPostFields(int $post_id): bool
    {
        return $this->deleteAllFieldsData($post_id);
    }// deleteAllPostFields


    /**
     * Get post fields data by name.
     * 
     * @param int $post_id The post ID.
     * @param string $field_name meta field name. If this field is empty then it will get all fields that matched this post ID.
     * @return mixed Return the row(s) of post fields data. If it was not found then return null.<br>
     *                          The return value may be unserialize if it is not scalar and not `null`.
     */
    public function get(int $post_id, string $field_name = '')
    {
        if (empty($field_name)) {
            $this->storageData = null;
        }

        return $this->getFields($post_id, $field_name);
    }// get


    /**
     * List multiple posts fields.
     * 
     * @since 0.0.14
     * @param array $postIds The multiple pos IDs to search in.
     * @param string $field_name Meta field name. If this field is empty then it will get all fields that matched post IDs.
     * @return array Return associative array where key is each object ID in the `$objectIds` and its result will be the same as we get from `getFields()` method with `$field_name` parameter.
     */
    public function listPostsFields(array $postIds, string $field_name = ''): array
    {
        return $this->listObjectsFields($postIds, $field_name);
    }// listPostsFields


    /**
     * Update post field.
     * 
     * If data is not exists then it will be call add data automatically.
     * 
     * @param int $post_id The post ID.
     * @param string $field_name Field name.
     * @param mixed $field_value Field value.
     * @param string|false $field_description Field description. Set to `false` (default) to not change.
     * @param mixed $previousValue Previous field value to check that it must be matched, otherwise it will not be update and return `false`. Set this to `false` to skip checking.
     * @return mixed Return field ID if data is not exists then it will be use `add()` method. Return `true` if update success, `false` for otherwise.
     */
    public function update(int $post_id, string $field_name, $field_value, $field_description = false, $previousValue = false)
    {
        return $this->updateFieldsData($post_id, $field_name, $field_value, $field_description, $previousValue);
    }// update


    /**
     * Update or insert multiple post fields at once.
     * 
     * If data is not exists in DB then it will be insert.
     * 
     * @param int $post_id The post ID.
     * @param array $data Associative array where key is match `field_name` column and value is match `field_value` column. Example:<pre>
     * array(
     *     'field_name1' => 'field value1',
     *     'field_name2' => 'field value2',
     *     // ...
     * )
     * </pre>
     * @param bool $updateFieldDescription Set to `true` to update/insert field description. Default is `true`.
     * @return bool Return `true` if update or insert completed, return `false` for otherwise.
     */
    public function updateMultiple(int $post_id, array $data, bool $updateFieldDescription = true): bool
    {
        $dataDesc = [];
        if (true === $updateFieldDescription) {
            // if it was marked to update field description.
            // currently, there is no field description defined in the class. (See RdbAdmin/Models/UserFieldsDb.php for reference.)
            // reserve this argument/parameter for the future.
        }

        return $this->updateFieldsMultipleData($post_id, $data, $dataDesc);
    }// updateMultiple


}
