<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Models;


/**
 * URL aliases class.
 */
class UrlAliasesDb extends \Rdb\System\Core\Models\BaseModel
{


    /**
     * @var array Allowed sort columns in db.
     */
    protected $allowedSort = ['alias_id', 'alias_content_type', 'alias_content_id', 'language', 'alias_url', 'alias_url_encoded', 'alias_redirect_to', 'alias_redirect_to_encoded', 'alias_redirect_code'];


    /**
     * @var string|null For checking that `addOrUpdate()` method use add or update process. The result will be 'add', 'update'. Default is null.
     */
    public $addOrUpdate = null;


    /**
     * @var string The table name.
     */
    protected $tableName;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->tableName = $this->Db->tableName('url_aliases');
    }// __construct


    /**
     * Add an URL alias.<br>
     * The data key `alias_url_encoded` will be auto generate from `alias_url` key.
     * 
     * Please check for duplicated URL before call this method.
     * 
     * @param array $data The associative array where key is column name and value is its value.
     * @return mixed Return inserted ID if successfully inserted, return `0` (zero), or `false` if failed to insert.
     * @throws \InvalidArgumentException Throw exception if required data array key is missing.
     */
    public function add(array $data)
    {
        if (!array_key_exists('alias_url', $data)) {
            throw new \InvalidArgumentException('The data array key `alias_url` is required.');
        }

        if (!isset($data['language'])) {
            $data['language'] = ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th');
        }

        $Url = new \Rdb\System\Libraries\Url($this->Container);
        if (!isset($data['alias_url_encoded'])) {
            $data['alias_url_encoded'] = $Url->rawUrlEncodeSegments($data['alias_url']);
        }
        if (isset($data['alias_redirect_to'])) {
            if (!isset($data['alias_redirect_to_encoded'])) {
                $data['alias_redirect_to_encoded'] = $Url->rawUrlEncodeAllParts($data['alias_redirect_to']);
            }
            if (!isset($data['alias_redirect_code'])) {
                $data['alias_redirect_code'] = 302;
            }
        }
        unset($Url);

        $insertResult = $this->Db->insert($this->tableName, $data);

        if ($insertResult === true) {
            $alias_id = $this->Db->PDO()->lastInsertId();
            return $alias_id;
        }
        return false;
    }// add


    /**
     * Detect automatically that data exists or not. If not exists use add, if exists use update.
     * 
     * Please check for duplicated URL before call this method.
     * 
     * @param array $data The data to add or update. Please set all columns and it will be automatically remove for those prevent change if use update.
     * @param array $where The associative array where its key is column name and value is its value.
     * @return mixed Return mixed value depend on add or update.
     */
    public function addOrUpdate(array $data, array $where = [])
    {
        $urlAliases = $this->get($where);
        if (!is_object($urlAliases) || empty($urlAliases) || $urlAliases === false) {
            // if not found, use add.
            unset($urlAliases);
            $this->addOrUpdate = 'add';
            return $this->add($data);
        } else {
            // if found, use update.
            unset($urlAliases);
            $this->addOrUpdate = 'update';
            return $this->update($data, $where);
        }
    }// addOrUpdate


    /**
     * Delete URL alias(es).
     * 
     * @param array $where The associative array where column name is the key and its value is the value pairs.
     * @return bool Return PDOStatement::execute(). Return `true` on success, `false` for otherwise.
     */
    public function delete(array $where): bool
    {
        return $this->Db->delete($this->tableName, $where);
    }// delete


    /**
     * Delete multiple items.
     * 
     * @param string $alias_content_type The content type that will be delete.
     * @param array $alias_content_ids The content IDs that will be delete.
     * @return bool Return PDOStatement::execute(). Return `true` on success, `false` for otherwise.
     */
    public function deleteMultiple(string $alias_content_type, array $alias_content_ids): bool
    {
        $values = [];
        $contentIdsPlaceholder = [];
        $i = 0;
        foreach ($alias_content_ids as $content_id) {
            $contentIdsPlaceholder[] = ':contentIdsIn' . $i;
            $values[':contentIdsIn' . $i] = $content_id;
            $i++;
        }// endforeach;
        unset($i, $content_id);

        $sql = 'DELETE FROM `' . $this->tableName . '` WHERE `alias_content_type` = :alias_content_type AND `alias_content_id` IN (' . implode(', ', $contentIdsPlaceholder) . ')';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        // bind whereValues
        $Sth->bindValue(':alias_content_type', $alias_content_type);
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $deleteResult = $Sth->execute();
        $Sth->closeCursor();
        unset($Sth);

        return $deleteResult;
    }// deleteMultiple


    /**
     * Get URL alias from conditions.
     * 
     * @param array $where The associative array where key is column name and value is its value.
     * @return mixed Return object if result was found, return `empty`, `null`, `false` if it was not found.
     */
    public function get(array $where = [])
    {
        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE 1';
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

        return $result;
    }// get


    /**
     * Check if URL is duplicated or not.
     * 
     * @param string $url The URL to check.
     * @param string $language The language to check. This is depend on configuration file, if it use cookies then language will not be checked.
     * @param int $editingId The editing content ID. Only require if check before update existing data.
     * @param string $editingContentType The editing content type. Only require if check before update existing data.
     * @param int $editingAliasId The editing alias ID. Only set this parameter if checking for redirect because redirection data don't have "content id" and "content type".
     * @return bool Return `true` if found duplicated, `false` for not duplicated.
     */
    public function isDuplicatedUrl(string $url, string $language, int $editingContentId = 0, string $editingContentType = '', $editingAliasId = 0): bool
    {
        // get file config
        if ($this->Container->has('Config')) {
            $Config = $this->Container->get('Config');
            $Config->setModule('');
        } else {
            $Config = new \Rdb\System\Config();
        }

        // detect language url base on the config file.
        if ($Config->get('languageMethod', 'language', 'url') === 'cookie') {
            // if config set to detect language using cookie.
            $checkLanguageUrlBase = false;
        } else {
            // if config set to detect language using URL
            $checkLanguageUrlBase = true;
        }

        if ($checkLanguageUrlBase === true && (is_null($language) || $language === '')) {
            $language = ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? $Config->getDefaultLanguage());
        }
        unset($Config);

        $sql = 'SELECT 
                `alias_id`, 
                `alias_content_type`, 
                `alias_content_id`, 
                `language`, 
                `alias_url`, 
                COUNT(`alias_id`) AS `total` 
            FROM `' . $this->tableName . '`
            WHERE `alias_url` = :alias_url';

        if ($checkLanguageUrlBase === true) {
            $sql .= ' AND `language` = :language';
        }

        $Sth = $this->Db->PDO()->prepare($sql);
        $Sth->bindValue(':alias_url', $url);
        if ($checkLanguageUrlBase === true) {
            $Sth->bindValue(':language', $language);
        }
        $Sth->execute();
        $result = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($sql, $Sth);

        // detect failure.
        if (!is_object($result) || empty($result)) {
            // if failure.
            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container->get('Logger');
                $Logger->write('modules/rdbcmsa/models/urlaliasesdb', 3, 'Unable to retrieve data from DB in ' . __FILE__ . ': ' . (__LINE__ - 10));
                unset($Logger);
            }
            return true;
        }

        if (
            (empty($editingContentId) && empty($editingAliasId)) || 
            ($editingContentId <= 0 && $editingAliasId <= 0)
        ) {
            // if check for add the URL.
            return ($result->total > 0);
        } else {
            // if check for editing.
            if ($result->total <= '0') {
                // if not found duplicated.
                return false;
            } else {
                // if seems to be found as duplicated.
                if ($editingContentId > 0) {
                    if (
                        $result->alias_content_id == $editingContentId && 
                        $result->alias_content_type == $editingContentType
                    ) {
                        // if its id and content type are matched currently editing.
                        // not really duplicated.
                        return false;
                    }
                } elseif ($editingAliasId > 0) {
                    if ($result->alias_id == $editingAliasId) {
                        // if its `$editingAliasId` matched in DB. this means it is editing this data
                        // not really duplicated.
                        return false;
                    }
                }
            }
        }

        return true;// always return found (duplicated) by default.
    }// isDuplicatedUrl


    /**
     * List items.
     * 
     * @param array $options The associative array options. Available options keys:<br>
     *                          `search` (string) the search term,<br>
     *                          `where` (array) the where conditions where key is column name and value is its value,<br>
     *                          `sortOrders` (array) the sort order where `sort` key is column name, `order` key is mysql order (ASC, DESC),<br>
     *                          `unlimited` (bool) set to `true` to show unlimited items, unset or set to `false` to show limited items,<br>
     *                          `limit` (int) limit items per page. maximum is 1000,<br>
     *                          `offset` (int) offset or start at record. 0 is first record,<br>
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
                $ConfigDb = new ConfigDb($this->Container);
                $options['limit'] = $ConfigDb->get('rdbadmin_AdminItemsPerPage', 20);
                unset($ConfigDb);
            } elseif (isset($options['limit']) && $options['limit'] > 1000) {
                $options['limit'] = 1000;
            }
        }

        $bindValues = [];
        $output = [];
        // sql left join is required for user listing that filter role.
        $sql = 'SELECT %*% FROM `' . $this->tableName . '` AS `url_aliases`
            WHERE 1';
        if (array_key_exists('search', $options) && is_scalar($options['search']) && !empty($options['search'])) {
            $sql .= ' AND (';
            $sql .= '`alias_content_type` LIKE :search';
            $sql .= ' OR `alias_content_id` LIKE :search';
            $sql .= ' OR `language` LIKE :search';
            $sql .= ' OR `alias_url` LIKE :search';
            $sql .= ' OR `alias_redirect_to` LIKE :search';
            $sql .= ' OR `alias_redirect_code` LIKE :search';
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

        // prepare and get 'total' records while not set limit and offset.
        $Sth = $this->Db->PDO()->prepare(str_replace('%*%', 'COUNT(`url_aliases`.`alias_id`) AS `total`', $sql));
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
                    $orderby[] = '`url_aliases`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
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
        $Sth = $this->Db->PDO()->prepare(str_replace('%*%', '*', $sql));
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($bindValues, $sql, $Sth);

        $output['items'] = $result;

        unset($result);
        return $output;
    }// listItems


    /**
     * Update an URL alias.
     * 
     * Please check for duplicated URL before call this method.
     * 
     * @param array $data The associative array where its key is column name and value is its value to update.
     * @param array $where The associative array where its key is column name and value is its value.
     * @return bool Return `true` on success update, `false` for otherwise.
     * @throws \InvalidArgumentException Throw exception if required data array key is missing.
     */
    public function update(array $data, array $where): bool
    {
        if (!array_key_exists('alias_url', $data)) {
            throw new \InvalidArgumentException('The data array key `alias_url` is required.');
        }

        // remove some data to prevent change.
        unset($data['language'], $data['alias_content_type'], $data['alias_content_id']);

        $Url = new \Rdb\System\Libraries\Url($this->Container);
        if (!isset($data['alias_url_encoded'])) {
            $data['alias_url_encoded'] = $Url->rawUrlEncodeSegments($data['alias_url']);
        }
        if (isset($data['alias_redirect_to'])) {
            if (!isset($data['alias_redirect_to_encoded'])) {
                $data['alias_redirect_to_encoded'] = $Url->rawUrlEncodeAllParts($data['alias_redirect_to']);
            }
            if (!isset($data['alias_redirect_code'])) {
                $data['alias_redirect_code'] = 302;
            }
        }
        unset($Url);

        return $this->Db->update($this->tableName, $data, $where);
    }// update


}
