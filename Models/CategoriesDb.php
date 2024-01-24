<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Models;


/**
 * Categories DB.
 * 
 * @since 0.0.1
 */
class CategoriesDb extends \Rundiz\NestedSet\NestedSet
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var \Rdb\System\Libraries\Db
     */
    protected $Db;


    /**
     * Class constructor.
     * 
     * @param \PDO $PDO The PDO class.
     * @param \Rdb\System\Container $Container The DI container.
     */
    public function __construct(\PDO $PDO, \Rdb\System\Container $Container = null)
    {
        $this->Container = $Container;

        if ($Container->has('Db')) {
            $this->Db = $Container->get('Db');

            $this->tableName = $this->Db->tableName('taxonomy_term_data');
            $this->idColumnName = 'tid';
            $this->parentIdColumnName = 'parent_id';
            $this->leftColumnName = 't_left';
            $this->rightColumnName = 't_right';
            $this->levelColumnName = 't_level';
            $this->positionColumnName = 't_position';
        }

        parent::__construct($PDO);

        include_once MODULE_PATH . DIRECTORY_SEPARATOR . 'RdbCMSA' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . 'php-array.php';
    }// __construct


    /**
     * Add a category data.
     * 
     * Also rebuild the nested set data such as left, right, level, etc.
     * 
     * @param array $data The associative array where key is column name and value is its value.
     * @return mixed Return inserted ID if successfully inserted, return `0` (zero), or `false` if failed to insert.
     */
    public function add(array $data)
    {
        $where = [];
        if (isset($data['parent_id']) && isset($data['t_type'])) {
            // if there is parent_id and type, get the new position.
            $where = [
                'whereString' => '`language` = :language AND `t_type` = :t_type',
                'whereValues' => [
                    ':language' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'th'),
                    ':t_type' => $data['t_type'],
                ],
            ];
            $data['t_position'] = $this->getNewPosition($data['parent_id'], $where);
        }

        $insertResult = $this->Db->insert($this->tableName, $data);

        if ($insertResult === true) {
            $tid = $this->PDO->lastInsertId();
            $this->rebuild($where);

            return $tid;
        }
        return false;
    }// add


    /**
     * Delete a single category.<br>
     * This method will delete a category from these tables: 
     *     `taxonomy_term_data`, 
     *     `taxonomy_fields`, 
     *     `taxonomy_index`.
     * 
     * This method will not `rebuild()` the data, you have to call it later once success.
     * 
     * @param int $tid The taxonomy ID.
     * @param string $t_type The taxonomy type.
     * @return bool Return PDOStatement::execute(). Return `true` on success, `false` for otherwise.
     */
    public function deleteACategory(int $tid, string $t_type): bool
    {
        $where = [];
        $where[$this->idColumnName] = $tid;
        $where['t_type'] = $t_type;
        $deleteResult = $this->Db->delete($this->tableName, $where);
        unset($where);

        if ($deleteResult === true) {
            // if deleted successfully.
            // delete from `taxonomy_fields` table.
            $sql = 'DELETE FROM `' . $this->Db->tableName('taxonomy_fields') . '` WHERE `tid` = :tid';
            $Sth = $this->PDO->prepare($sql);
            unset($sql);
            $Sth->bindValue(':tid', $tid, \PDO::PARAM_INT);
            $Sth->execute();
            $Sth->closeCursor();
            unset($Sth);

            // also delete from `taxonomy_index` table.
            $sql = 'DELETE FROM `' . $this->Db->tableName('taxonomy_index') . '` WHERE `tid` = :tid';
            $Sth = $this->PDO->prepare($sql);
            unset($sql);
            $Sth->bindValue(':tid', $tid, \PDO::PARAM_INT);
            $Sth->execute();
            $Sth->closeCursor();
            unset($Sth);
        }

        return $deleteResult;
    }// deleteACategory


    /**
     * Get a single taxonomy data without its parent or children.
     * 
     * @param array $where The associative array where key is column name and value is its value.
     * @param array $options The addition options. Available options:<br>
     *                          `skipTaxonomyFields` (bool) Skip retrieve `taxonomy_fields`table or not. Default is `true` means skip it, `false` means do not skip it.<br>
     * @return mixed Return object if result was found, return `empty`, `null`, `false` if it was not found.
     */
    public function get(array $where = [], array $options = [])
    {
        $sql = 'SELECT `taxonomy_term_data`.*, `url_aliases`.*, `taxonomy_term_data`.`language` AS `language` FROM `' . $this->tableName . '` AS `taxonomy_term_data`
            LEFT JOIN `' . $this->Db->tableName('url_aliases') . '` AS `url_aliases` 
                ON `taxonomy_term_data`.`tid` = `url_aliases`.`alias_content_id` 
                AND `taxonomy_term_data`.`language` = `url_aliases`.`language` 
                AND `taxonomy_term_data`.`t_type` = `url_aliases`.`alias_content_type` 
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

        $Sth = $this->PDO->prepare($sql);
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $sql, $value, $values);

        $Sth->execute();
        $result = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($Sth);

        if (is_object($result)) {
            if (isset($options['skipTaxonomyFields']) && $options['skipTaxonomyFields'] === false) {
                // get taxonomy fields.
                $TaxonomyFieldsDb = new TaxonomyFieldsDb($this->Container);
                $taxonomyFieldsResults = $TaxonomyFieldsDb->get($result->tid);
                $taxonomyFields = [];
                if (is_array($taxonomyFieldsResults)) {
                    foreach ($taxonomyFieldsResults as $eachField) {
                        $taxonomyFields[$eachField->field_name] = $eachField;
                    }// endforeach;
                    unset($eachField);
                }
                unset($taxonomyFieldsResults);
                $result->taxonomyFields = $taxonomyFields;
                unset($taxonomyFields, $TaxonomyFieldsDb);
            }
        }

        return $result;
    }// get


    /**
     * Get taxonomy from selected item and fetch its parent in a line until root item.<br>
     * Example: There are taxonomy tree like this. Root1 > 1.1 > 1.1.1 > 1.1.1.1<br>
     * Assume that you selected at 1.1.1. So, the result will be Root1 > 1.1 > 1.1.1<br>
     * But if you set 'skipCurrent' to true the result will be Root1 > 1.1
     * 
     * Warning! Even this method has options for search, custom where conditions
     * but it is recommended that you should set the option to select only specific item.<br>
     * This method is intended to show results from a single target.
     * 
     * The columns `left`, `right` must have been built before using this method, otherwise the result will be incorrect.
     * 
     * This method was copied from `\Rundiz\NestedSet\NestedSet->getTaxonomyWithParents()` and add `LEFT JOIN`.
     * 
     * @link http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/ Original source.
     * @param array $options Available options: <br>
     *                      `filter_taxonomy_id` (int) The filter taxonomy ID.<br>
     *                      `search` (array) The search array format is..<br>
     *                              `array('columns' => array('name', 'column2', 'column3'), 'searchValue' => 'search string')`<br>
     *                      `where` (array) The custom where conditions. The array format is..<br>
     *                              ``array('whereString' => '(`node`.`columnName` = :value1 AND `node`.`columnName2` = :value2)', 'whereValues' => array(':value1' => 'lookup value 1', ':value2' => 'lookup value2'))``<br>
     *                              or just only `whereString`.<br>
     *                              ``array('whereString' => '(`node`.`columnName` = \'value\'))``<br>
     *                      `skipCurrent` (bool) Set to `true` to skip currently selected item.
     * @return mixed Return array object of taxonomy data if found, return null if not found.
     */
    public function getTaxonomyWithParents(array $options = []): array
    {
        $sql = 'SELECT `parent`.*, `url_aliases`.*';
        $sql .= ' FROM `' . $this->tableName . '` AS `node`,';
        $sql .= ' `' . $this->tableName . '` AS `parent`';
        // Add left join to allow usage of url aliases.
        $sql .= ' LEFT JOIN `' . $this->Db->tableName('url_aliases') . '` AS `url_aliases` 
            ON `alias_content_type` = `parent`.`t_type` 
            AND `alias_content_id` = `parent`.`tid` 
            AND `url_aliases`.`language` = `parent`.`language`';
        $sql .= ' WHERE';
        $sql .= ' `node`.`' . $this->leftColumnName . '` BETWEEN `parent`.`' . $this->leftColumnName . '` AND `parent`.`' . $this->rightColumnName . '`';

        if (isset($options['filter_taxonomy_id'])) {
            $sql .= ' AND `node`.`' . $this->idColumnName . '` = :filter_taxonomy_id';
        }

        if (
            isset($options['search']) && 
            is_array($options['search']) && 
            array_key_exists('columns', $options['search']) && 
            is_array($options['search']['columns']) && 
            array_key_exists('searchValue', $options['search'])
        ) {
            $haveSearch = true;
            $sql .= ' AND (';
            $array_keys = array_keys($options['search']['columns']);
            $last_array_key = array_pop($array_keys);
            foreach ($options['search']['columns'] as $key => $column) {
                $sql .= '`node`.`' . $column . '` LIKE :search';
                if ($key !== $last_array_key) {
                    $sql .= ' OR ';
                }
            }// endforeach;
            unset($array_keys, $column, $key, $last_array_key);
            $sql .= ')';
        }

        if (
            isset($options['where']['whereString']) &&
            is_string($options['where']['whereString'])
        ) {
            $sql .= ' AND ' . $options['where']['whereString'];
        }

        $sql .= ' GROUP BY `parent`.`' . $this->idColumnName . '`';
        $sql .= ' ORDER BY `parent`.`' . $this->leftColumnName . '`';

        $Sth = $this->PDO->prepare($sql);
        if (isset($options['filter_taxonomy_id'])) {
            $Sth->bindValue(':filter_taxonomy_id', $options['filter_taxonomy_id'], \PDO::PARAM_INT);
        }
        if (isset($options['search']) && is_array($options['search']) && array_key_exists('searchValue', $options['search'])) {
            $Sth->bindValue(':search', '%'.$options['search']['searchValue'].'%', \PDO::PARAM_STR);
        }
        if (isset($options['where']['whereValues']) && is_array($options['where']['whereValues'])) {
            foreach ($options['where']['whereValues'] as $placeholder => $value) {
                $Sth->bindValue($placeholder, $value);
            }// endforeach;
            unset($placeholder, $value);
        }
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();

        if (isset($options['skipCurrent']) && $options['skipCurrent'] === true) {
            unset($result[count($result)-1]);
        }
        unset($haveSearch, $sql, $Sth);

        if ($result !== false && $result !== null) {
            return $result;
        } else {
            return null;
        }
    }// getTaxonomyWithParents


    /**
     * List categories using MySQL v8.0+, MariaDB v10.2.2+ `RECURSIVE` statement.
     * 
     * @link http://mysqlserverteam.com/mysql-8-0-labs-recursive-common-table-expressions-in-mysql-ctes/ MySQL blog about new `RECURSIVE CTE`.
     * @link https://mariadb.com/kb/en/recursive-common-table-expressions-overview/ MariaDB user manual about `RECURSIVE CTE`.
     * @param array $options Available options:
     *              `cache` (bool) Set to `true` to cache the query. Default is `false`.<br>
     *              `cacheExpires` (int) Set number of TTL. Default is 10 minutes (number in seconds).<br>
     *              `search` (string) the search term,<br>
     *              `taxonomy_id_in` (array) The taxonomy ID to look with `IN()` MySQL function.<br>
     *                  The array values must be integer, example `array(1,3,4,5)`.<br>
     *              `where` (array) the where conditions where key is column name and value is its value,<br>
     *              `unlimited` (bool) set to `true` to show unlimited items, unset or set to `false` to show limited items,<br>
     *              `limit` (int) limit items per page. maximum is 1000,<br>
     *              `offset` (int) offset or start at record. 0 is first record,<br>
     *              `list_flatten` (bool) Set to `true` to list the result flatten.<br>
     *              `skipTaxonomyFields` (bool) Skip retrieve `taxonomy_fields`table or not. Default is `true` means skip it, `false` means do not skip it.<br>
     * @return array Return array with `total` and `items` in keys.
     */
    public function listRecursive(array $options = []): array
    {
        // example query. 
        /*
            WITH RECURSIVE `taxonomy` AS (
                SELECT * FROM `rdb_taxonomy_term_data` 
                    WHERE `tid` IN (6,5)
                UNION
                SELECT `child`.*
                    FROM `rdb_taxonomy_term_data` AS `child`, `taxonomy` AS `parent`
                    WHERE `child`.`parent_id` = `parent`.`tid`
            )
            SELECT * FROM `taxonomy` ORDER BY `t_left` ASC
        */

        if (isset($options['cache']) && true === $options['cache']) {
            $Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
                $this->Container,
                [
                    'cachePath' => STORAGE_PATH . '/cache/Modules/RdbCMSA/Models/CategoriesDb',
                ]
            ))->getCacheObject();
            $cacheKey = __FUNCTION__ . '_' . md5(json_encode($options));
            $cacheExpires = ($options['cacheExpires'] ?? (10 * 60));// default cache is 10 minutes.

            if ($Cache->has($cacheKey)) {
                unset($cacheExpires);
                return $Cache->get($cacheKey);
            }
        }

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
        $sql = 'WITH RECURSIVE `taxonomy_term_data` AS (';
        $sql .= '    SELECT * FROM `' . $this->tableName . '`';
        $sql .= '        WHERE 1';

        if (array_key_exists('search', $options) && is_scalar($options['search']) && !empty($options['search'])) {
            $sql .= ' AND (';
            $sql .= '`t_name` LIKE :search';
            $sql .= ' OR `t_description` LIKE :search';
            $sql .= ' OR `t_head_value` LIKE :search';
            $sql .= ')';
            $bindValues[':search'] = '%' . $options['search'] . '%';
        }

        if (isset($options['taxonomy_id_in']) && is_array($options['taxonomy_id_in'])) {
            foreach ($options['taxonomy_id_in'] as $key => $eachTid) {
                if (!is_numeric($eachTid)) {
                    // if id is not number, remove it.
                    unset($options['taxonomy_id_in'][$key]);
                }
            }// endforeach;

            if (!empty($options['taxonomy_id_in'])) {
                $sql .= '        AND `' . $this->idColumnName . '` IN (';
                $i = 1;
                foreach ($options['taxonomy_id_in'] as $key => $eachTid) {
                    $sql .= ':tidIn' . $i;
                    if ($key !== array_key_last($options['taxonomy_id_in'])) {
                        $sql .= ',';
                    }
                    $i++;
                }// endforeach;
                $sql .= ')';
            }
            unset($eachTid, $i, $key);
        }// endif id IN().

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
        }// endif where in options

        $sql .= '    UNION';
        $sql .= '    SELECT `child`.*';
        $sql .= '        FROM `' . $this->tableName . '` AS `child`, `taxonomy_term_data` AS `parent`';
        $sql .= '        WHERE `child`.`' . $this->parentIdColumnName . '` = `parent`.`' . $this->idColumnName . '`';
        $sql .= ')';// end WITH RECURSIVE.
        $sql .= ' SELECT `taxonomy_term_data`.*, `url_aliases`.*, `taxonomy_term_data`.`language` AS `language`';
        $sql .= ' FROM `taxonomy_term_data`';
        $sql .= ' LEFT JOIN `' . $this->Db->tableName('url_aliases') . '` AS `url_aliases` 
            ON `alias_content_type` = `taxonomy_term_data`.`t_type` 
            AND `alias_content_id` = `taxonomy_term_data`.`tid` 
            AND `url_aliases`.`language` = `taxonomy_term_data`.`language`';
        $sql .= ' ORDER BY `' . $this->leftColumnName . '` ASC';

        $Sth = $this->PDO->prepare($sql);
        $this->listRecursiveBindValues($Sth, $options, $bindValues);
        $Sth->execute();
        $result = $Sth->fetchAll();

        $output = [];
        $output['total'] = count($result);
        $Sth->closeCursor();
        unset($Sth);

        $previousSql = $sql;
        // limited or unlimited.
        if (!isset($options['unlimited']) || (isset($options['unlimited']) && $options['unlimited'] !== true)) {
            // if limited.
            $sql .= ' LIMIT ' . $options['limit'] . ' OFFSET ' . $options['offset'];
        }

        if ($sql !== $previousSql) {
            // if it is the same, no need to re-query to get items because we already have them.
            // prepare and get 'items'.
            $Sth = $this->Db->PDO()->prepare($sql);
            $this->listRecursiveBindValues($Sth, $options, $bindValues);
            $Sth->execute();
            $result = $Sth->fetchAll();
            $Sth->closeCursor();
        }
        $output['items'] = $result;
        unset($bindValues, $previousSql, $sql, $Sth);

        if (!empty($result) && is_array($result)) {
            $TaxonomyFieldsDb = new TaxonomyFieldsDb($this->Container);
            foreach ($result as $row) {
                if (isset($options['skipTaxonomyFields']) && $options['skipTaxonomyFields'] === false) {
                    $taxonomyFieldsResults = $TaxonomyFieldsDb->get($row->tid);
                    $taxonomyFields = [];
                    if (is_array($taxonomyFieldsResults)) {
                        foreach ($taxonomyFieldsResults as $eachField) {
                            $taxonomyFields[$eachField->field_name] = $eachField;
                        }// endforeach;
                        unset($eachField);
                    }
                    unset($taxonomyFieldsResults);
                    $row->taxonomyFields = $taxonomyFields;
                    unset($taxonomyFields);
                }// endif not skip taxonomy_fields.
            }// endforeach;
            unset($row, $TaxonomyFieldsDb);
        }// endif; $result not empty and is array.

        if (
            !empty($result) && 
            is_array($result) &&
            (
                !isset($options['list_flatten']) || 
                (isset($options['list_flatten']) && $options['list_flatten'] !== true)
            )
        ) {
            $result = parent::listTaxonomyBuildTreeWithChildren($result, $options);
        }// endif; populate tree with children.

        // set 'items' result
        $output['items'] = $result;

        unset($result);

        if (isset($Cache) && isset($cacheKey) && isset($cacheExpires)) {
            $Cache->set($cacheKey, $output, $cacheExpires);
            unset($Cache, $cacheExpires, $cacheKey);
        }
        return $output;
    }// listRecursive


    /**
     * Bind values for `listRecursive()` method.
     * 
     * This method was called from `listRecursive()` method.
     * 
     * @param \PDOStatement $Sth
     * @param array $options
     * @param array $bindValues
     */
    protected function listRecursiveBindValues(\PDOStatement $Sth, array $options, array $bindValues = [])
    {
        // bind values for id IN().
        if (isset($options['taxonomy_id_in']) && is_array($options['taxonomy_id_in']) && !empty($options['taxonomy_id_in'])) {
            $i = 1;
            foreach ($options['taxonomy_id_in'] as $key => $eachTid) {
                $Sth->bindValue(':tidIn' . $i, $eachTid);
                $i++;
            }// endforeach;
            unset($eachTid, $i, $key);
        }

        if (is_array($bindValues)) {
            // bind whereValues
            foreach ($bindValues as $placeholder => $value) {
                $Sth->bindValue($placeholder, $value);
            }// endforeach;
            unset($placeholder, $value);
        }
    }// listRecursiveBindValues


    /**
     * Build tree data with children.
     * 
     * @see Rundiz\NestedSet\NestedSet:listTaxonomyBuildTreeWithChildren The original source code.
     * @param array $result The array item get from fetchAll() method using the PDO.
     * @param array $options Available options: <br>
     *                          `list_flatten` (bool) Set to `true` to list the result flatten.
     * @return array Return array data of formatted values.
     */
    public function listRecursiveBuildTreeWithChildren(array $result, array $options = []): array
    {
        if (isset($options['list_flatten']) && $options['list_flatten'] === true) {
            return $result;
        }

        $items = [];
        foreach ($result as $row) {
            $items[$row->{$this->parentIdColumnName}][] = $row;
        }// endforeach;

        foreach ($result as $row) {
            if (isset($items[$row->{$this->idColumnName}])) {
                $row->children = $items[$row->{$this->idColumnName}];
            }
        }// endforeach;

        $result = ($items[0] ?? array_shift($items));// this is important ([0]) for prevent duplicate items

        if (!is_array($result)) {
            $result = [];
        }

        unset($items, $row);
        return $result;
    }// listRecursiveBuildTreeWithChildren


    /**
     * Update a category data.
     * 
     * This method will not `rebuild()` the data, you have to call it later once success.
     * 
     * @param array $data The associative array where its key is column name and value is its value to update.
     * @param array $where The associative array where its key is column name and value is its value.
     * @return bool Return `true` on success update, `false` for otherwise.
     */
    public function update(array $data, array $where): bool
    {
        // remove some data to prevent change.
        unset($data['language'], $data['t_type']);

        $output = $this->Db->update($this->tableName, $data, $where);

        return $output;
    }// update


}
