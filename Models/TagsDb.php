<?php


namespace Rdb\Modules\RdbCMSA\Models;


/**
 * Tags Db
 * 
 * @since 0.0.1
 */
class TagsDb extends TaxonomyTermDataDb
{


    /**
     * Add a tag data.
     * 
     * @param array $data The associative array where key is column name and value is its value.
     * @return mixed Return inserted ID if successfully inserted, return `0` (zero), or `false` if failed to insert.
     */
    public function add(array $data)
    {
        // remove or reset unnecessary data.
        $data['parent_id'] = 0;
        $data['t_status'] = 1;

        $where = [];
        if (isset($data['t_type'])) {
            // if there is type, get the new position.
            $where = [
                'whereString' => '`t_type` = :t_type',
                'whereValues' => [':t_type' => $data['t_type']],
            ];
            $data['t_position'] = $this->getNewPosition(0, $where);
        } else {
            $data['t_position'] = 1;
        }

        return parent::add($data);
    }// add


    /**
     * Get new position for taxonomy in the selected parent.
     * 
     * @param int $parent_id The parent ID. If root, set this to 0.
     * @param array $where Where array structure will be like this.<br>
     * <pre>
     * array(
     *     'whereString' => '(`columnName` = :value1 AND `columnName2` = :value2)',
     *     'whereValues' => array(':value1' => 'lookup value 1', ':value2' => 'lookup value2'),
     * )</pre>
     * @return int Return the new position in the same parent.<br>
     *              WARNING! If there are no results, either because there are no data <br>
     *              or the results according to the conditions cannot be found. It always returns 1.
     */
    public function getNewPosition(int $parent_id, array $where = []): int
    {
        $sql = 'SELECT * FROM `' . $this->tableName . '`';
        $sql .= ' WHERE `parent_id` = :parent_id';
        if (isset($where['whereString']) && is_string($where['whereString'])) {
            $sql .= ' AND ' . $where['whereString'];
        }
        $sql .= ' ORDER BY `t_position` DESC';

        $Sth = $this->Db->PDO()->prepare($sql);

        $Sth->bindValue(':parent_id', $parent_id, \PDO::PARAM_INT);
        if (isset($where['whereValues']) && is_array($where['whereValues'])) {
            foreach ($where['whereValues'] as $bindName => $bindValue) {
                $Sth->bindValue($bindName, $bindValue);
            }// endforeach;
            unset($bindName, $bindValue);
        }

        $Sth->execute();
        $row = $Sth->fetch();
        $Sth->closeCursor();
        unset($sql, $Sth);

        if ($row != null) {
            return (int) ($row->t_position + 1);
        } else {
            unset($row);
            return 1;
        }
    }// getNewPosition


    /**
     * List tags.
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
                $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
                $options['limit'] = $ConfigDb->get('rdbadmin_AdminItemsPerPage', 20);
                unset($ConfigDb);
            } elseif (isset($options['limit']) && $options['limit'] > 1000) {
                $options['limit'] = 1000;
            }
        }

        $bindValues = [];
        $output = [];
        $sql = 'SELECT %*% FROM `' . $this->tableName . '` AS `taxonomy_term_data`
            LEFT JOIN `' . $this->Db->tableName('url_aliases') . '` AS `url_aliases` 
                ON `alias_content_type` = `taxonomy_term_data`.`t_type` 
                AND `alias_content_id` = `taxonomy_term_data`.`tid` 
                AND `url_aliases`.`language` = `taxonomy_term_data`.`language`
            WHERE 1';
        if (array_key_exists('search', $options) && is_scalar($options['search']) && !empty($options['search'])) {
            $sql .= ' AND (';
            $sql .= '`taxonomy_term_data`.`t_name` LIKE :search';
            $sql .= ' OR `taxonomy_term_data`.`t_description` LIKE :search';
            $sql .= ' OR `taxonomy_term_data`.`t_head_value` LIKE :search';
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
                    if ($sort['sort'] == 'alias_url') {
                        $orderby[] = '`url_aliases`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
                    } else {
                        $orderby[] = '`taxonomy_term_data`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
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

        // limited or unlimited.
        if (!isset($options['unlimited']) || (isset($options['unlimited']) && $options['unlimited'] !== true)) {
            // if limited.
            $sql .= ' LIMIT ' . $options['limit'] . ' OFFSET ' . $options['offset'];
        }

        // prepare and get 'items'.
        $Sth = $this->Db->PDO()->prepare(str_replace('%*%', '`taxonomy_term_data`.*, `url_aliases`.*, `taxonomy_term_data`.`language` AS `language`', $sql));
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
     * Update a tag  data.
     * 
     * @param array $data The associative array where its key is column name and value is its value to update.
     * @param array $where The associative array where its key is column name and value is its value.
     * @return bool Return `true` on success update, `false` for otherwise.
     */
    public function update(array $data, array $where): bool
    {
        // remove some data to prevent change.
        unset($data['parent_id'], $data['language'], $data['t_type']);

        return parent::update($data, $where);
    }// update


}
