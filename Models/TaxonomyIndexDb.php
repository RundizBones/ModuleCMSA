<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Models;


/**
 * Taxonomy index between taxonomy and posts.
 * 
 * @since 0.0.1
 */
class TaxonomyIndexDb extends \Rdb\System\Core\Models\BaseModel
{


    use Traits\CommonModelTrait;


    /**
     * @var array Contain update debug info.
     */
    public $debugUpdate = [];


    /**
     * @var string Table name.
     */
    protected $tableName;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->tableName = $this->Db->tableName('taxonomy_index');
    }// __construct


    /**
     * Add multiple taxonomy index data at a time.
     * 
     * Also update count of `t_total` on `taxonomy_term_data` table.
     * 
     * @param array $dataTi The taxonomy index data array. Example:
     * <pre>
     * array(
     *     array('post_id' => 5, 'tid' => 2),
     *     array('post_id' => 5, 'tid' => 6),
     *     array('post_id' => 5, 'tid' => 7),
     * )
     * </pre>
     * @return int Return number of inserted records.
     */
    public function add(array $dataTi): int
    {
        $countInserted = 0;
        foreach ($dataTi as $data) {
            if (is_array($data) && isset($data['tid'])) {
                if (!isset($data['ti_position'])) {
                    $data['ti_position'] = $this->getNewPosition((int) $data['tid']);
                }
                if (!isset($data['ti_create'])) {
                    $data['ti_create'] = date('Y-m-d H:i:s');
                }
                if (!isset($data['ti_create_gmt'])) {
                    $data['ti_create_gmt'] = gmdate('Y-m-d H:i:s', strtotime($data['ti_create']));
                }

                $insertResult = $this->Db->insert($this->tableName, $data);

                if ($insertResult === true) {
                    $countInserted++;

                    $sql = 'UPDATE `' . $this->Db->tableName('taxonomy_term_data') . '` SET `t_total` = `t_total` + 1 WHERE `tid` = :tid';
                    $Sth = $this->Db->PDO()->prepare($sql);
                    unset($sql);
                    $Sth->bindValue(':tid', $data['tid']);
                    $Sth->execute();
                    $Sth->closeCursor();
                    unset($Sth);
                }
            }
        }// endforeach;
        unset($data);

        return $countInserted;
    }// add


    /**
     * Count taxonomy for selected `tid`.
     * 
     * @param int $tid The taxonomy ID.
     * @return int Return total count.
     */
    public function countTaxonomy(int $tid): int
    {
        $count = 0;

        $sql = 'SELECT COUNT(*) AS `total` FROM `' . $this->tableName . '` WHERE `tid` = :tid';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        $Sth->bindValue(':tid', $tid, \PDO::PARAM_INT);
        $Sth->execute();
        $result = $Sth->fetchColumn();
        $Sth->closeCursor();
        unset($Sth);

        if (is_numeric($result)) {
            $count = (int) $result;
        }
        unset($result);

        return $count;
    }// countTaxonomy


    /**
     * Delete multiple items.
     * 
     * Also update count of deleted posts.
     * 
     * @param array $postIdsArray The post IDs. Only required if you want to delete posts.
     * @param array $tidsArray The taxonomy IDs. Only required if you want to delete taxonomies.
     * @return bool Return `true` on success, `false` on failure.
     * @throws \InvalidArgumentException Throw the exception if none argument are empty.
     */
    public function deleteMultiple(array $postIdsArray = [], array $tidsArray = []): bool
    {
        if (empty($postIdsArray) && empty($tidsArray)) {
            throw new \InvalidArgumentException('Onf of the arguments $postIdsArray or $tidsArray must be set.');
        }
        $multipleColumnsConnector = 'AND';

        // list all items to collect what tids will be delete and count them for subtract in `taxonomy_term_data` table.
        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE ';
        $values = [];
        if (!empty($postIdsArray)) {
            $postIdsInPlaceholder = [];
            $i = 0;
            foreach ($postIdsArray as $post_id) {
                $postIdsInPlaceholder[] = ':postIdsIn' . $i;
                $values[':postIdsIn' . $i] = $post_id;
                $i++;
            }// endforeach;
            unset($i, $post_id);

            $sql .= ' `post_id` IN (' . implode(', ', $postIdsInPlaceholder) . ')';
        }
        if (!empty($postIdsArray) && !empty($tidsArray)) {
            $sql .= ' ' . $multipleColumnsConnector . ' ';
        }
        if (!empty($tidsArray)) {
            $tidsInPlaceholder = [];
            $i = 0;
            foreach ($tidsArray as $tid) {
                $tidsInPlaceholder[] = ':tidsIn' . $i;
                $values[':tidsIn' . $i] = $tid;
                $i++;
            }// endforeach;
            unset($i, $tid);

            $sql .= ' `tid` IN (' . implode(', ', $tidsInPlaceholder) . ')';
        }
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        // bind whereValues
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Sth);
        // end list all items. -----------------------------

        if (empty($result) && is_array($result)) {
            // if result is empty.
            return true;
        } elseif (!is_array($result)) {
            // if result is not array. (???)
            return false;
        }

        // count tid that will be delete. -----------------
        $tidsDelete = [];
        foreach ($result as $row) {
            if (array_key_exists($row->tid, $tidsDelete) && is_numeric($tidsDelete[$row->tid])) {
                $tidsDelete[$row->tid]++;
            } else {
                $tidsDelete[$row->tid] = 1;
            }
        }// endforeach;
        unset($result, $row);
        // end count tid that will be delete. ------------

        // start delete selected items. ------------------
        $sql = 'DELETE FROM `' . $this->tableName . '` WHERE';
        if (isset($postIdsInPlaceholder)) {
            $sql .= ' `post_id` IN (' . implode(', ', $postIdsInPlaceholder) . ')';
            unset($postIdsInPlaceholder);
        }
        if (!empty($postIdsArray) && !empty($tidsArray)) {
            $sql .= ' ' . $multipleColumnsConnector . ' ';
            unset($multipleColumnsConnector);
        }
        if (isset($tidsInPlaceholder)) {
            $sql .= ' `tid` IN (' . implode(', ', $tidsInPlaceholder) . ')';
            unset($tidsInPlaceholder);
        }
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        // bind whereValues
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $deleteResult = $Sth->execute();
        $Sth->closeCursor();
        unset($Sth);
        // end delete selected items. -------------------

        if ($deleteResult === true) {
            // if deleted successfully.
            if (!empty($postIdsArray)) {
                // if not only delete taxonomy.
                // update count on `taxonomy_term_data` table.
                foreach ($tidsDelete as $tid => $count) {
                    $sql = 'UPDATE `' . $this->Db->tableName('taxonomy_term_data') . '` SET `t_total` = `t_total` - :count WHERE `tid` = :tid';
                    $Sth = $this->Db->PDO()->prepare($sql);
                    unset($sql);
                    $Sth->bindValue(':count', $count, \PDO::PARAM_INT);
                    $Sth->bindValue(':tid', $tid, \PDO::PARAM_INT);
                    $Sth->execute();
                    $Sth->closeCursor();
                    unset($Sth);
                }// endforeach;
                unset($count, $tid, $tidsDelete);
            }
            return true;
        } else {
            return false;
        }
    }// deleteMultiple


    /**
     * Get a single taxonomy index.
     * 
     * @param array $where The associative array where key is column name and value is its value.
     * @return mixed Return object if result was found, return `empty`, `null`, `false` if it was not found.
     */
    public function get(array $where = [])
    {
        $sql = 'SELECT * FROM `' . $this->tableName . '` AS `taxonomy_index`
            INNER JOIN `' . $this->Db->tableName('taxonomy_term_data') . '` AS `taxonomy_term_data`
                ON `taxonomy_term_data`.`tid` = `taxonomy_index`.`tid`
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
     * Get new position.
     * 
     * @param int $tid The taxonomy ID.
     * @return int Return new position that is ready to insert to `ti_position`.
     */
    protected function getNewPosition(int $tid): int
    {
        $output = 1;

        $sql = 'SELECT `tid`, `ti_position` FROM `' . $this->tableName . '` WHERE `tid` = :tid ORDER BY `ti_position` DESC LIMIT 0, 1';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        $Sth->bindValue(':tid', $tid, \PDO::PARAM_INT);
        $Sth->execute();
        $result = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($Sth);

        if (is_object($result) && !empty($result)) {
            $output = (int) (intval($result->ti_position) + 1);
            unset($result);
        }

        return $output;
    }// getNewPosition


    /**
     * List items.
     * 
     * @param array $options The associative array options. Available options keys:<br>
     *              `cache` (bool) Set to `true` to be able to cache the query by plugins. Default is `false`.<br>
     *              `where` (array) the where conditions where key is column name and value is its value,<br>
     *              `tidsIn` (array) the taxonomy IDs to use in the sql command `WHERE IN (...)`<br>
     *              `tidsNotIn` (array) the taxonomy IDs to use in the sql command `WHERE NOT IN (...)`<br>
     *              `sortOrders` (array) the sort order where `sort` key is column name, `order` key is mysql order (ASC, DESC),<br>
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

        $bindValues = [];
        $output = [];
        $sql = 'SELECT %*% FROM `' . $this->tableName . '` AS `taxonomy_index`
            INNER JOIN `' . $this->Db->tableName('taxonomy_term_data') . '` AS `taxonomy_term_data`
                ON `taxonomy_term_data`.`tid` = `taxonomy_index`.`tid`
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

        if (array_key_exists('tidsIn', $options) && is_array($options['tidsIn']) && !empty($options['tidsIn'])) {
            // taxonomy IDs IN(..).
            $sql .= ' AND';

            $tidsInPlaceholder = [];
            $i = 0;
            foreach ($options['tidsIn'] as $tid) {
                $tidsInPlaceholder[] = ':tidsIn' . $i;
                $bindValues[':tidsIn' . $i] = $tid;
                $i++;
            }// endforeach;
            unset($i, $tid);

            $sql .= ' `taxonomy_index`.`tid` IN (' . implode(', ', $tidsInPlaceholder) . ')';
            unset($tidsInPlaceholder);
        }

        if (array_key_exists('tidsNotIn', $options) && is_array($options['tidsNotIn']) && !empty($options['tidsNotIn'])) {
            // taxonomy IDs NOT IN(..).
            $sql .= ' AND';

            $tidsNotInPlaceholder = [];
            $i = 0;
            foreach ($options['tidsNotIn'] as $tid) {
                $tidsNotInPlaceholder[] = ':tidsNotIn' . $i;
                $bindValues[':tidsNotIn' . $i] = $tid;
                $i++;
            }// endforeach;
            unset($i, $tid);

            $sql .= ' `taxonomy_index`.`tid` NOT IN (' . implode(', ', $tidsNotInPlaceholder) . ')';
            unset($tidsNotInPlaceholder);
        }

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
                        $orderby[] = '`taxonomy_index`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
                    }
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

        // prepare and get 'total' records while not set limit and offset.
        $Sth = $this->Db->PDO()->prepare(str_replace('%*%', '*', $sql));
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $Sth->execute();
        $output['items'] = $Sth->fetchAll();
        $output['total'] = count($output['items']);
        $Sth->closeCursor();
        unset($bindValues, $sql, $Sth);

        if (isset($options['cache']) && true === $options['cache'] && isset($cacheArgs)) {
            // if there is an option to allow to set/get cache.
            $this->cmtSetCacheListItems($cacheArgs, $output);
            unset($cacheArgs);
        }// endif; there is cache option.

        return $output;
    }// listItems


    /**
     * Update multiple taxonomy index at a time.
     * 
     * This will be remove taxonomies that was removed from the form.<br>
     * Also update count of `t_total` on `taxonomy_term_data` table.
     * 
     * @param array $dataTi The taxonomy index data array. Example:
     * <pre>
     * array(
     *     array('post_id' => 5, 'tid' => 2),
     *     array('post_id' => 5, 'tid' => 6),
     *     array('post_id' => 5, 'tid' => 7),
     * )
     * </pre>
     * @param array $where The associative array where its key is column name and value is its value. Suggested:
     * <pre>
     * array(
     *     'post_id' => 1,
     *     'taxonomy_term_data.t_type' => 'category',// for delete removed taxonomeis from the form.
     * )
     * </pre>
     * @return int Return number of updated records.
     * @throws \InvalidArgumentException Throw exception if required array key is not exists in $where.
     */
    public function update(array $dataTi, array $where): int
    {
        if (!array_key_exists('post_id', $where)) {
            throw new \InvalidArgumentException('The required array key `post_id` is not exists in `$where` argument.');
        }

        // make $dataTi to be 2D array for easy checking.
        $dataTi2D = [];
        foreach ($dataTi as $data) {
            $dataTi2D[] = $data['tid'];
        }// endforeach;
        unset($data);

        // remove taxonomies that removed from the form. -----------------------------------
        $listItems = $this->listItems([
            'where' => $where,
            'tidsNotIn' => $dataTi2D,
        ]);
        unset($dataTi2D);

        if (isset($listItems['items']) && is_array($listItems['items'])) {
            $this->debugUpdate['removed'] = [];
            $sql = 'UPDATE `' . $this->Db->tableName('taxonomy_term_data') . '` SET `t_total` = `t_total` - 1 WHERE `tid` = :tid';
            foreach ($listItems['items'] as $row) {
                // count subtract the taxonomy that will be deleted.
                $Sth = $this->Db->PDO()->prepare($sql);
                $Sth->bindValue(':tid', $row->tid);
                $subtractResult = $Sth->execute();
                $Sth->closeCursor();
                unset($Sth);

                // then delete.
                if ($subtractResult === true) {
                    $deleteResult = $this->Db->delete(
                        $this->tableName, 
                        [
                            'post_id' => $where['post_id'],
                            'tid' => $row->tid,
                        ]
                    );
                }

                if (isset($deleteResult) && $deleteResult === true) {
                    $this->debugUpdate['removed'][] = $row->tid;
                }
                unset($deleteResult, $subtractResult);
            }// endforeach;
            unset($row, $sql);
        }
        unset($listItems);
        // end remove. -----------------------------------------------------------------------------

        $countSaved = 0;
        $this->debugUpdate['add'] = [];
        $this->debugUpdate['update'] = [];
        foreach ($dataTi as $data) {
            if (is_array($data) && isset($data['tid'])) {
                $whereTidExists = [];
                $whereTidExists['taxonomy_index.post_id'] = $where['post_id'];
                $whereTidExists['taxonomy_index.tid'] = $data['tid'];
                $result = $this->get($whereTidExists);
                unset($whereTidExists);

                if (is_object($result) && !empty($result)) {
                    // if post_id and tid already exists.
                    // remove unnecessary data
                    $tid = $data['tid'];
                    unset($data['post_id'], $data['tid'], $data['ti_create'], $data['ti_create_gmt']);

                    if (!empty($data)) {
                        $saveResult = $this->Db->update(
                            $this->tableName, 
                            $data, 
                            [
                                'post_id' => $where['post_id'],
                                'tid' => $tid,
                            ]
                        );

                        if ($saveResult === true) {
                            $countSaved++;
                            $this->debugUpdate['update'][] = $tid;
                        }
                        unset($saveResult, $tid);
                    } else {
                        $countSaved++;
                    }
                } else {
                    // if post_id and tid is not exists.
                    // set value for insert.
                    if (!isset($data['ti_position'])) {
                        $data['ti_position'] = $this->getNewPosition((int) $data['tid']);
                    }
                    if (!isset($data['ti_create'])) {
                        $data['ti_create'] = date('Y-m-d H:i:s');
                    }
                    if (!isset($data['ti_create_gmt'])) {
                        $data['ti_create_gmt'] = gmdate('Y-m-d H:i:s', strtotime($data['ti_create']));
                    }

                    $saveResult = $this->Db->insert($this->tableName, $data);

                    if ($saveResult === true) {
                        $sql = 'UPDATE `' . $this->Db->tableName('taxonomy_term_data') . '` SET `t_total` = `t_total` + 1 WHERE `tid` = :tid';
                        $Sth = $this->Db->PDO()->prepare($sql);
                        unset($sql);
                        $Sth->bindValue(':tid', $data['tid']);
                        $countUpTidResult = $Sth->execute();
                        $Sth->closeCursor();
                        unset($Sth);

                        if ($countUpTidResult === true) {
                            $countSaved++;
                            $this->debugUpdate['add'][] = $data['tid'];
                        }
                        unset($countUpTidResult);
                    }
                    unset($saveResult);
                }// endif post_id and tid exists or not

                unset($whereTidExists);
            }
        }// endforeach;
        unset($data);

        return $countSaved;
    }// update


}
