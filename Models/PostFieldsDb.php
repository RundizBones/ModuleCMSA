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
     * Update post field multiple rows at once.
     * 
     * If data is not exists then it will be call add data automatically.
     * 
     * @see \Rdb\Modules\RdbCMSA\Models\PostFields::update() for more details that its attributes will be array keys.
     * @param array $data The array format must be..<pre>
     * array(
     *     array(
     *         'post_id' => 82,
     *         'field_name' => 'extra_field_name_string_only',
     *         'field_value' => 'mixed type (non scalar will be serialize automatically).',
     *         'field_description' => 'Optional. The field description. set to `false` to not change.',
     *         'previousValue' => 'Optional. Previous value for check that must be matched or it will not update and this array index in return will be `false`. set this to `false` to skip checking.',
     *     ),
     *     // ...array
     * );</pre>
     * @return array Return array with the same index and its value will be result from `update()` method of this class.
     * @throws \InvalidArgumentException Throw exception if the array format is invalid.
     */
    public function updateMultiple(array $data): array
    {
        $output = [];

        if (!empty($data)) {
            // the first loop will be for checking valid $data format.
            foreach ($data as $item) {
                if (
                    !is_array($item) ||
                    (
                        !array_key_exists('post_id', $item) ||
                        !array_key_exists('field_name', $item) ||
                        !array_key_exists('field_value', $item)
                    ) ||
                    !is_string($item['field_name'])
                ) {
                    // if invalid array format.
                    throw new \InvalidArgumentException('Invalid array format for $data.');
                }
            }// endforeach;
            // end first loop. ------------------------------

            // the second loop will be add/update data.
            foreach ($data as $index => $item) {
                $post_id = $item['post_id'];
                $field_name = $item['field_name'];
                $field_value = $item['field_value'];
                $field_description = ($item['field_description'] ?? false);
                $previousValue = ($item['previousValue'] ?? false);
                $output[$index] = $this->update($post_id, $field_name, $field_value, $field_description, $previousValue);
            }// endforeach;
            unset($field_description, $field_name, $field_value, $index, $item, $post_id, $previousValue);
            // end second loop. -------------------------
        }

        return $output;
    }// updateMultiple


}
