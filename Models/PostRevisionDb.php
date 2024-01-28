<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Models;


/**
 * Post revision DB.
 * 
 * @since 0.0.1
 */
class PostRevisionDb extends \Rdb\System\Core\Models\BaseModel
{


    use Traits\CommonModelTrait;


    /**
     * @var array Allowed sort columns in db.
     */
    protected $allowedSort = ['revision_id', 'post_id', 'user_id', 'revision_status', 'revision_date', 'revision_date_gmt'];


    /**
     * @var string The `posts` table name.
     */
    protected $tableName;


    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->tableName = $this->Db->tableName('post_revision');
    }// __construct


    /**
     * Delete post revision.
     * 
     * @param array $where The associative array where column name is the key and its value is the value pairs.
     * @return bool Return PDOStatement::execute(). Return `true` on success, `false` for otherwise.
     */
    public function delete(array $where): bool
    {
        return $this->Db->delete($this->tableName, $where);
    }// delete


    /**
     * Get a single post revision data.
     * 
     * @param array $where The associative array where key is column name and value is its value.
     * @return mixed Return object if result was found, return `empty`, `null`, `false` if it was not found.
     */
    public function get(array $where = [])
    {
        $sql = 'SELECT * FROM `' . $this->tableName . '` AS `post_revision`
            LEFT JOIN `' . $this->Db->tableName('users') . '` AS `users`
                ON `post_revision`.`user_id` = `users`.`user_id`
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

        return $result;
    }// get


    /**
     * List post revisions.
     * 
     * @param array $options The associative array options. Available options keys:<br>
     *              `cache` (bool) Set to `true` to be able to cache the query by plugins. Default is `false`.<br>
     *              `where` (array) the where conditions where key is column name and value is its value,<br>
     *              `sortOrders` (array) the sort order where `sort` key is column name, `order` key is mysql order (ASC, DESC),<br>
     *              `unlimited` (bool) set to `true` to show unlimited items, unset or set to `false` to show limited items,<br>
     *              `limit` (int) limit items per page. maximum is 1000,<br>
     *              `offset` (int) offset or start at record. 0 is first record,<br>
     * @return array Return associative array with `total` and `items` in keys.
     */
    public function listItems(array $options = []): array
    {
        if (isset($options['cache']) && true === $options['cache']) {
            // if there is an option to get/set cache.
            $cacheArgs = func_get_args();
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

        $bindValues = [];
        $output = [];
        $sql = 'SELECT %*% FROM `' . $this->tableName . '` AS `post_revision`
            LEFT JOIN `' . $this->Db->tableName('users') . '` AS `users`
                ON `post_revision`.`user_id` = `users`.`user_id`
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
                    $orderby[] = '`post_revision`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
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
                '`post_revision`.*, '
                    . '`users`.`user_id`, `users`.`user_login`, `users`.`user_email`, `users`.`user_display_name`', 
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

        $output['items'] = $result;

        unset($result);

        if (isset($options['cache']) && true === $options['cache'] && isset($cacheArgs)) {
            // if there is an option to allow to set/get cache.
            $this->cmtSetCacheListItems($cacheArgs, $output);
            unset($cacheArgs);
        }// endif; there is cache option.

        return $output;
    }// listItems


}
