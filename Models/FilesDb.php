<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Models;


/**
 * Files DB.
 * 
 * @since 0.0.1
 * @property-read string $tableName
 */
class FilesDb extends \Rdb\System\Core\Models\BaseModel
{


    /**
     * @var array Allowed sort columns in db.
     */
    protected $allowedSort = ['file_id', 'user_id', 'file_folder', 'file_visibility', 'file_name', 'file_original_name', 'file_mime_type', 'file_ext', 'file_size', 'file_status', 'file_add', 'file_add_gmt', 'file_update', 'file_update_gmt'];


    /**
     * @var string Root public folder name for upload files.
     */
    public $rootPublicFolderName = 'rdbadmin-public';


    /**
     * @var string The table name.
     */
    protected $tableName;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container
     * @param string $rootPublicFolderName Root public folder name for upload files.
     */
    public function __construct(\Rdb\System\Container $Container, string $rootPublicFolderName = '')
    {
        parent::__construct($Container);

        $this->tableName = $this->Db->tableName('files');

        if (!empty($rootPublicFolderName)) {
            $this->rootPublicFolderName = $rootPublicFolderName;
        }
    }// __construct


    /**
     * Magic get.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
        return ;
    }// __get


    /**
     * Add new file data.
     * 
     * @param array $data The data for `posts` table. The associative array where key is column name and value is its value.
     * @return mixed Return inserted ID if successfully inserted, return `0` (zero), or `false` if failed to insert.
     */
    public function add(array $data)
    {
        // force add required data.
        $Cookie = new \Rdb\Modules\RdbAdmin\Libraries\Cookie($this->Container);
        $Cookie->setEncryption('rdbaLoggedinKey');
        $cookieData = $Cookie->get('rdbadmin_cookie_users');
        $data['user_id'] = ($cookieData['user_id'] ?? 0);
        unset($Cookie, $cookieData);

        // add required data only if not exists.
        if (!array_key_exists('file_status', $data)) {
            $data['file_status'] = 1;
        }
        if (!isset($data['file_add'])) {
            $data['file_add'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['file_add_gmt'])) {
            $data['file_add_gmt'] = gmdate('Y-m-d H:i:s', strtotime($data['file_add']));
        }
        if (!isset($data['file_update'])) {
            $data['file_update'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['file_update_gmt'])) {
            $data['file_update_gmt'] = gmdate('Y-m-d H:i:s', strtotime($data['file_update']));
        }

        $insertResult = $this->Db->insert($this->tableName, $data);

        if ($insertResult === true) {
            // if insert success.
            // get last id from that table.
            $file_id = $this->Db->PDO()->lastInsertId();
            unset($insertResult);
            return (int) $file_id;
        }
        return false;
    }// add


    /**
     * Count how many posts contain the searching file.
     * 
     * @param string $fileUrl The file URL to search.
     * @param int $file_id The file ID to search.
     * @return int Return total posts that contain this file.
     */
    public function countSearchFileInPosts(string $fileUrl, int $file_id): int
    {
        $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
        $imageSizes = $FilesSubController->getThumbnailSizes();
        unset($FilesSubController);

        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . $this->rootPublicFolderName);
        // build search values.
        $bindValues = [];
        $placeholders = [];
        $i = 1;
        foreach ($imageSizes as $sizeString => $sizeArray) {
            $bindValues[':searchthumb' . $i] = '%' . $FileSystem->addSuffixFileName($fileUrl, '_' . $sizeString) . '%';
            $placeholders[] = '`post_revision`.`revision_body_value` LIKE :searchthumb' . $i;
            $placeholders[] = '`post_revision`.`revision_body_summary` LIKE :searchthumb' . $i;
            $i++;
        }// endforeach;
        unset($FileSystem, $i, $sizeArray, $sizeString);

        $sql = 'SELECT COUNT(DISTINCT `posts`.`post_id`) AS `total` FROM `' . $this->Db->tableName('posts') . '` AS `posts`
            INNER JOIN `' . $this->Db->tableName('post_revision') . '` AS `post_revision`
                ON `posts`.`post_id` = `post_revision`.`post_id`
            WHERE `post_feature_image` = :file_id
            OR (
                `post_revision`.`revision_body_value` LIKE :search
                OR `post_revision`.`revision_body_summary` LIKE :search' . "\n";
        if (!empty($placeholders)) {
            $sql .= ' OR ' . implode("\n" . ' OR ', $placeholders) . "\n";
        }
        unset($placeholders);
        $sql .= '            )';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);

        $Sth->bindValue(':file_id', $file_id);
        $Sth->bindValue(':search', '%' . $fileUrl . '%');
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($bindValues, $placeholder, $value);

        $Sth->execute();
        $total = $Sth->fetchColumn();
        $Sth->closeCursor();
        unset($Sth);

        if (is_numeric($total)) {
            return (int) $total;
        }
        return 0;
    }// countSearchFileInPosts


    /**
     * Delete a single file.
     * 
     * Also update `posts`.`post_feature_image` where contains selected id (if provided in `$where`) to `null`.
     * 
     * @param array $where The associative array where its key is column name and value is its value.
     * @return bool Return PDOStatement::execute(). Return `true` on success, `false` for otherwise.
     */
    public function deleteAFile(array $where): bool
    {
        if (isset($where['file_id'])) {
            // if there is `file_id` key.
            // update posts.post_feature_image where it contains this id to `null`.
            $this->Db->update($this->Db->tableName('posts'), ['post_feature_image' => null], ['post_feature_image' => $where['file_id']]);
        }

        // delete this file id from files table.
        return $this->Db->delete($this->tableName, $where);
    }// deleteAFile


    /**
     * Delete all files in DB (not actual file) where it is in selected folder including sub folders.
     * 
     * This will not delete the actual file.
     * 
     * @param string $folder The selected folder.
     * @return bool Return `true` on success, `false` on failure.
     */
    public function deleteFilesInFolder(string $folder): bool
    {
        // list items to update feature image in `posts` table.
        $sql = 'SELECT `file_id`, `file_folder` FROM `' . $this->tableName . '` WHERE `file_folder` = :actual_file_folder OR `file_folder` LIKE :file_folder';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        $Sth->bindValue(':actual_file_folder', $folder);// example books/cartoon
        $Sth->bindValue(':file_folder', $folder . '/%');// example books/cartoon/* such as books/cartoon/onepiece, books/cartoon/dragonball
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Sth);

        if (is_array($result)) {
            $i = 0;
            $placeholders = [];
            $bindValues = [];
            foreach ($result as $row) {
                $placeholders[] = ':file_id' . $i;
                $bindValues[':file_id' . $i] = $row->file_id;
                $i++;
            }// endforeach;
            unset($i, $row);
        }
        unset($result);

        if (
            isset($placeholders) && 
            isset($bindValues) &&
            !empty($placeholders) &&
            !empty($bindValues)
        ) {
            // if contain placeholders and values, this means they are for update.
            // update `post_feature_image` where contain these file IDs in `posts`  table to `NULL`.
            $sql = 'UPDATE `' . $this->Db->tableName('posts') . '` SET `post_feature_image` = NULL WHERE `post_feature_image` IN (' . implode(', ', $placeholders) . ')';
            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            foreach ($bindValues as $placeholder => $value) {
                $Sth->bindValue($placeholder, $value);
            }// endforeach;
            unset($placeholder, $value);
            $Sth->execute();
            $Sth->closeCursor();
            unset($Sth);
        }
        unset($bindValues, $placeholders);

        // delete from records.
        $sql = 'DELETE FROM `' . $this->tableName . '` WHERE `file_folder` = :actual_file_folder OR `file_folder` LIKE :file_folder';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        $Sth->bindValue(':actual_file_folder', $folder);// example books/cartoon
        $Sth->bindValue(':file_folder', $folder . '/%');// example books/cartoon/* such as books/cartoon/onepiece, books/cartoon/dragonball
        $deleteResult = $Sth->execute();
        $Sth->closeCursor();
        unset($Sth);

        return $deleteResult;
    }// deleteFilesInFolder


    /**
     * Get a single file data.
     * 
     * @param array $where The associative array where key is column name and value is its value.
     * @param array $options Available options:<br>
     *                          `getFileFullPath` (bool) Set to `true` to get file's full path. Default is `false` or do not get it.<br>
     * @return mixed Return object if result was found, return `empty`, `null`, `false` if it was not found.
     */
    public function get(array $where = [], array $options = [])
    {
        $sql = 'SELECT `files`.*, 
                `users`.`user_id`, `users`.`user_login`, `users`.`user_email`, `users`.`user_display_name`, 
                `files`.`user_id` AS `user_id`
            FROM `' . $this->tableName . '` AS `files`
            LEFT JOIN `' . $this->Db->tableName('users') . '` AS `users` 
                ON `files`.`user_id` = `users`.`user_id` 
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

        if (is_object($result) && !empty($result)) {
            $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
            if (in_array(strtolower($result->file_ext), $FilesSubController->imageExtensions)) {
                // if extension is image file.
                $Url = new \Rdb\System\Libraries\Url($this->Container);
                $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem();
                // get thumbnails. --------------------------
                $tmpThumb = $this->getThumbnails($result, $FileSystem, $Url);

                if (!empty($tmpThumb)) {
                    $result->thumbnails = $tmpThumb;
                }
                // end get thumbnails. ----------------------
                unset($FileSystem, $tmpThumb, $Url);
            }
            unset($FilesSubController);

            if (!empty($result->file_metadata)) {
                $result->file_metadata = json_decode($result->file_metadata);
            }

            if (isset($options['getFileFullPath']) && $options['getFileFullPath'] === true) {
                $result->fileFullPath = $this->getFileFullPath($result);
            }
        }

        return $result;
    }// get


    /**
     * Get file full path.
     * 
     * This will not check for file exists.
     * 
     * This method was called from `listItems()`, `get()` methods.
     * 
     * @param \stdClass $row The object from each result row.
     * @return string Return full path to file depend on `file_visibility` type.
     */
    protected function getFileFullPath(\stdClass $row): string
    {
        $output = $this->getFileWithFolderFullPath($row);
        $output .= DIRECTORY_SEPARATOR;

        if ($row->file_visibility === '0' || $row->file_visibility === '1') {
            // if in storage folder (0) or in public/rdbadmin-public folder (1).
            $output .= $row->file_name;
        } elseif ($row->file_visibility === '2') {
            // if related from root folder (2).
            $output .= $row->file_custom_path;
        }

        return $output;
    }// getFileFullPath


    /**
     * Get file related path start from the column `file_folder`.
     * 
     * @param \stdClass $row
     * @return string Return related path start from data in `file_folder` column. Not begins with trailing slash.
     */
    public function getFileRelatePath(\stdClass $row): string
    {
        $output = '';

        if (!empty($row->file_folder)) {
            $output .= $row->file_folder . '/';
        }
        $output .= $row->file_name;

        return $output;
    }// getFileRelatePath


    /**
     * Get file beginning path and append with data in `file_folder` column. The result will be in full path to `file_folder` but not include the file name.
     * 
     * This method was called from `getThumbnails()`, `getFileFullPath()` methods.
     * 
     * @param \stdClass $row The result object from DB.
     * @return string Return full path with NO trailing slash or back-slash.
     */
    protected function getFileWithFolderFullPath(\stdClass $row): string
    {
        $output = '';

        if ($row->file_visibility === '0') {
            // if in storage folder.
            $output = STORAGE_PATH;
            if (!empty($row->file_folder)) {
                // if not empty file_folder. this is for prevent directory separator-empty (\) and connect with `file_name` will be double separators.
                $output .= DIRECTORY_SEPARATOR . $row->file_folder;
            }
        } elseif ($row->file_visibility === '1') {
            // if in public/rdbadmin-public folder.
            $output = PUBLIC_PATH;
            $output .= DIRECTORY_SEPARATOR . $this->rootPublicFolderName;
            if (!empty($row->file_folder)) {
                // if not empty file_folder. this is for prevent directory separator-empty (\) and connect with `file_name` will be double separators.
                $output .= DIRECTORY_SEPARATOR . $row->file_folder;
            }
        } elseif ($row->file_visibility === '2') {
            // if in custom path related from framework's root.
            $output = ROOT_PATH;
        }

        return $output;
    }// getFileWithFolderFullPath


    /**
     * Get thumbnails.
     * 
     * This method was called from `listItems()`, `get()` methods.
     * 
     * @param \stdClass $row The result object from DB.
     * @param \Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem The file system class.
     * @param \Rdb\System\Libraries\Url $Url The framework URL class.
     * @return array Return array with `thumbXXX` keys if file exists.
     */
    protected function getThumbnails(
        \stdClass $row, 
        \Rdb\Modules\RdbCMSA\Libraries\FileSystem $FileSystem,
        \Rdb\System\Libraries\Url $Url
    ): array {
        $tmpThumb = [];
        $thumbnailFullPath = $this->getFileWithFolderFullPath($row);
        $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
        $thumbnailSizes = $FilesSubController->getThumbnailSizes();

        if ($row->file_visibility === '0') {
            // if in storage folder.
            foreach ($thumbnailSizes as $name => list($width, $height)) {
                if (is_file($thumbnailFullPath . DIRECTORY_SEPARATOR . $FileSystem->addSuffixFileName($row->file_name, '_' . $name))) {
                    $tmpThumb[$name . 'Fullpath'] = $thumbnailFullPath . DIRECTORY_SEPARATOR . $FileSystem->addSuffixFileName($row->file_name, '_' . $name);
                }
            }// endforeach;
            unset($height, $name, $width);
        } elseif ($row->file_visibility === '1') {
            // if in public/rdbadmin-public folder.
            $thumbnailUrlPrefix = $Url->getDomainProtocol() . $Url->getPublicUrl() . '/' . $this->rootPublicFolderName;
            if (!empty($row->file_folder)) {
                // if not empty file_folder. this is for prevent slash-empty (/) and connect with thumbnail below will be double slash (//image_thumbxxx.jpg).
                $thumbnailUrlPrefix .= '/' . $row->file_folder;
            }
            foreach ($thumbnailSizes as $name => list($width, $height)) {
                if (is_file($thumbnailFullPath . DIRECTORY_SEPARATOR . $FileSystem->addSuffixFileName($row->file_name, '_' . $name))) {
                    $tmpThumb[$name] = $thumbnailUrlPrefix . '/' . $FileSystem->addSuffixFileName($row->file_name, '_' . $name);
                }
            }// endforeach;
            unset($height, $name, $width);
        } elseif ($row->file_visibility === '2') {
            // if in custom path related from framework's root.
            foreach ($thumbnailSizes as $name => list($width, $height)) {
                if (is_file($thumbnailFullPath . DIRECTORY_SEPARATOR . $FileSystem->addSuffixFileName($row->file_custom_path, '_' . $name))) {
                    $tmpThumb[$name . 'Fullpath'] = $thumbnailFullPath . DIRECTORY_SEPARATOR . $FileSystem->addSuffixFileName($row->file_custom_path, '_' . $name);
                }
            }// endforeach;
            unset($height, $name, $width);
        }// endif file_visibility
        unset($FilesSubController, $thumbnailFullPath, $thumbnailUrlPrefix);

        return $tmpThumb;
    }// getThumbnails


    /**
     * List files in DB.
     * 
     * @param array $options The associative array options. Available options keys:<br>
     *                          `search` (string) the search term,<br>
     *                          `file_id_in` (array) The file ID to look with `IN()` MySQL function.<br>
     *                              The array values must be integer, example `array(1,3,4,5)`.<br>
     *                          `where` (array) the where conditions where key is column name and value is its value,<br>
     *                          `filterMime` (string) the mime type to filter. Example: `image/` will include `image/gif`, `image/jpeg`, and so on.<br>
     *                          `sortOrders` (array) the sort order where `sort` key is column name, `order` key is mysql order (ASC, DESC),<br>
     *                              If the `sort` key is `_field()` then it will be use `FIELD(column, x, x)` where column is the `file_id_in`.<br>
     *                          `unlimited` (bool) set to `true` to show unlimited items, unset or set to `false` to show limited items,<br>
     *                          `limit` (int) limit items per page. maximum is 1000,<br>
     *                          `offset` (int) offset or start at record. 0 is first record,<br>
     *                          `getFileFullPath` (bool) Set to `true` to get file's full path. Default is `false` or do not get it.<br>
     * @return array Return associative array with `total` and `items` in keys.
     */
    public function listItems(array $options = []): array
    {
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

        $bindValues = [];
        $output = [];
        $sql = 'SELECT %*% FROM `' . $this->tableName . '` AS `files`
            LEFT JOIN `' . $this->Db->tableName('users') . '` AS `users` 
                ON `files`.`user_id` = `users`.`user_id` 
            WHERE 1';
        if (array_key_exists('search', $options) && is_scalar($options['search']) && !empty($options['search'])) {
            $sql .= ' AND (';
            $sql .= '`files`.`file_folder` LIKE :search';
            $sql .= ' OR `files`.`file_custom_path` LIKE :search';
            $sql .= ' OR `files`.`file_name` LIKE :search';
            $sql .= ' OR `files`.`file_original_name` LIKE :search';
            $sql .= ' OR `files`.`file_mime_type` LIKE :search';
            $sql .= ' OR `files`.`file_media_name` LIKE :search';
            $sql .= ' OR `files`.`file_media_description` LIKE :search';
            $sql .= ' OR `files`.`file_media_keywords` LIKE :search';
            $sql .= ')';
            $bindValues[':search'] = '%' . $options['search'] . '%';
        }

        if (array_key_exists('file_id_in', $options) && is_array($options['file_id_in']) && !empty($options['file_id_in'])) {
            // file IDs IN(..).
            $sql .= ' AND';

            $fileIdsInPlaceholder = [];
            $i = 0;
            foreach ($options['file_id_in'] as $tid) {
                $fileIdsInPlaceholder[] = ':fileIdsIn' . $i;
                $bindValues[':fileIdsIn' . $i] = $tid;
                $i++;
            }// endforeach;
            unset($i, $tid);

            $sql .= ' `files`.`file_id` IN (' . implode(', ', $fileIdsInPlaceholder) . ')';
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

        if (isset($options['filterMime'])) {
            $sql .= ' AND `files`.`file_mime_type` LIKE :filterMime';
            $bindValues[':filterMime'] = $options['filterMime'] . '%';
        }

        // prepare and get 'total' records while not set limit and offset.
        $Sth = $this->Db->PDO()->prepare(str_replace('%*%', 'COUNT(*) AS `total`', $sql));
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $Sth->execute();
        $output['total'] = $Sth->fetchColumn();
        $Sth->closeCursor();
        unset($Sth);

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
                    $orderby[] = '`files`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
                } elseif (
                    is_array($sort) &&
                    array_key_exists('sort', $sort) &&
                    $sort['sort'] === '_field()' &&
                    array_key_exists('file_id_in', $options) && 
                    is_array($options['file_id_in']) && 
                    !empty($options['file_id_in'])
                ) {
                    $orderby[] = 'FIELD(`files`.`file_id`, ' . implode(', ', $fileIdsInPlaceholder) . ')';
                }

                unset($naturalSort);
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
                '`files`.*, '
                    . '`users`.`user_id`, `users`.`user_login`, `users`.`user_email`, `users`.`user_display_name`, '
                    . '`files`.`user_id` AS `user_id`', 
                $sql
            )
        );
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $fileIdsInPlaceholder, $value);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($bindValues, $sql, $Sth);

        if (is_array($result)) {
            $Url = new \Rdb\System\Libraries\Url($this->Container);
            $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem();
            $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();

            foreach ($result as $index => $row) {
                if (in_array(strtolower($row->file_ext), $FilesSubController->imageExtensions)) {
                    // if extension is image file.
                    // get thumbnails. --------------------------
                    $tmpThumb = $this->getThumbnails($row, $FileSystem, $Url);

                    if (!empty($tmpThumb)) {
                        $row->thumbnails = $tmpThumb;
                    }
                    unset($tmpThumb);
                    // end get thumbnails. ----------------------
                }

                if (!empty($row->file_metadata)) {
                    $row->file_metadata = json_decode($row->file_metadata);
                }

                if (isset($options['getFileFullPath']) && $options['getFileFullPath'] === true) {
                    $row->fileFullPath = $this->getFileFullPath($row);
                }
            }// endforeach;

            unset($FilesSubController, $FileSystem, $index, $row, $Url);
        }

        $output['items'] = $result;

        unset($result);
        return $output;
    }// listItems


    /**
     * Rename the URL that contain `file_folder` data in `post_revision` table.
     * 
     * Example: `renameFileFolderInPostRevision('http://domain.tld/rdbadmin-public/book', 'http://domain.tld/rdbadmin-public/books')`.<br>
     * The example above will be rename http://domain.tld/rdbadmin-public/book/any/path/image.jpg to http://domain.tld/rdbadmin-public/books/any/path/image.jpg
     * 
     * @param string $currentUrl The current URL (full URL).
     * @param string $newUrl The new URL (full URL).
     * @return bool Return `true` on success update, `false` for otherwise.
     */
    public function renameFileFolderInPostRevision(string $currentUrl, string $newUrl): bool
    {
        $sql = 'UPDATE `' . $this->Db->tableName('post_revision') . '`
            SET `revision_body_value` = REPLACE(`revision_body_value`, :currentUrl, :newUrl), 
                `revision_body_summary` = REPLACE(`revision_body_summary`, :currentUrl, :newUrl)
            WHERE `revision_body_value` LIKE :like_currentUrl
                OR `revision_body_summary` LIKE :like_currentUrl';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        $Sth->bindValue(':currentUrl', $currentUrl);
        $Sth->bindValue(':like_currentUrl', '%' . $currentUrl . '%');
        $Sth->bindValue(':newUrl', $newUrl);
        $output = $Sth->execute();
        $Sth->closeCursor();
        unset($Sth);

        return $output;
    }// renameFileFolderInPostRevision


    /**
     * Rename any folders in `file_folder` column that start with specific `$file_folder`.
     * 
     * Example: `rename('test', 'new-test');` will be rename `file_folder` column that contain these values: test, test/first, test/first/grade, test/second, test/xxx
     * 
     * @param string $file_folder The current value of `file_folder`.
     * @param string $new_file_folder The new value of `file_folder`.
     * @return bool Return `true` on success update, `false` for otherwise.
     */
    public function renameFolder(string $file_folder, string $new_file_folder): bool
    {
        $sql = 'UPDATE ' . $this->tableName . '
            SET `file_folder` = REPLACE(`file_folder`, :file_folder, :new_file_folder)
            WHERE `file_folder` = :file_folder
                OR `file_folder` LIKE :file_folder_parent';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        $Sth->bindValue(':file_folder', $file_folder);
        $Sth->bindValue(':file_folder_parent', $file_folder . '/%');
        $Sth->bindValue(':new_file_folder', $new_file_folder);
        $output = $Sth->execute();
        $Sth->closeCursor();
        unset($Sth);

        return $output;
    }// renameFolder


    /**
     * Update a file  data.
     * 
     * @param array $data The associative array where its key is column name and value is its value to update.
     * @param array $where The associative array where its key is column name and value is its value.
     * @return bool Return `true` on success update, `false` for otherwise.
     */
    public function update(array $data, array $where): bool
    {
        // remove some data to prevent change.
        unset(
            $data['file_id'], 
            $data['user_id'], 
            $data['file_folder'], 
            $data['file_visibility'],
            $data['file_custom_path'],
            $data['file_name'],
            $data['file_original_name'],
            $data['file_mime_type'],
            $data['file_ext'],
            $data['file_size'],
            $data['file_add'],
            $data['file_add_gmt']
        );

        // add some data if missing
        if (!array_key_exists('file_update', $data)) {
            $data['file_update'] = date('Y-m-d H:i:s');
        }
        if (!array_key_exists('file_update_gmt', $data)) {
            $data['file_update_gmt'] = gmdate('Y-m-d H:i:s', strtotime($data['file_update']));
        }

        $output = $this->Db->update($this->tableName, $data, $where);

        return $output;
    }// update


}
