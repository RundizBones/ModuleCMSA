<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Models;


/**
 * Taxonomy term data table DB.
 * 
 * @since 0.0.1
 */
class TaxonomyTermDataDb extends \Rdb\System\Core\Models\BaseModel
{


    /**
     * @var array Allowed sort columns in db.
     */
    protected $allowedSort = ['tid', 't_total', 't_name', 't_description', 't_position', 't_left', 't_right', 'alias_url'];


    /**
     * @var string The table name.
     */
    protected $tableName;


    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->tableName = $this->Db->tableName('taxonomy_term_data');
    }// __construct


    /**
     * Add a taxonomy term data.
     * 
     * @param array $data The associative array where key is column name and value is its value.
     * @return mixed Return inserted ID if successfully inserted, return `0` (zero), or `false` if failed to insert.
     */
    public function add(array $data)
    {
        $insertResult = $this->Db->insert($this->tableName, $data);

        if ($insertResult === true) {
            $tid = $this->Db->PDO()->lastInsertId();

            return $tid;
        }
        return false;
    }// add


    /**
     * Delete a taxonomy term data.<br>
     * This method will delete a taxonomy term data from these tables: 
     *     `taxonomy_term_data`, 
     *     `taxonomy_fields`, 
     *     `taxonomy_index`.
     * 
     * @param int $tid The taxonomy ID.
     * @param string $t_type The taxonomy type.
     * @return bool Return PDOStatement::execute(). Return `true` on success, `false` for otherwise.
     */
    public function delete(int $tid, string $t_type): bool
    {
        $where = [];
        $where['tid'] = $tid;
        $where['t_type'] = $t_type;
        $deleteResult = $this->Db->delete($this->tableName, $where);
        unset($where);

        if ($deleteResult === true) {
            // if deleted successfully.
            // delete from `taxonomy_fields` table.
            $sql = 'DELETE FROM `' . $this->Db->tableName('taxonomy_fields') . '` WHERE `tid` = :tid';
            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            $Sth->bindValue(':tid', $tid, \PDO::PARAM_INT);
            $Sth->execute();
            $Sth->closeCursor();
            unset($Sth);

            // also delete from `taxonomy_index` table.
            $sql = 'DELETE FROM `' . $this->Db->tableName('taxonomy_index') . '` WHERE `tid` = :tid';
            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            $Sth->bindValue(':tid', $tid, \PDO::PARAM_INT);
            $Sth->execute();
            $Sth->closeCursor();
            unset($Sth);
        }

        return $deleteResult;
    }// delete


    /**
     * Delete multiple items with its related tables.
     * 
     * Delete taxonomy data on taxonomy_term_data, taxonomy_fields, taxonomy_index, translation_matcher, url_aliases tables.
     * 
     * @since 0.0.6
     * @param array $tids
     * @return bool
     */
    public function deleteItemsWithRelatedTables(array $tids): bool
    {
        $i = 0;
        $placeholders = [];
        $bindValues = [];
        $deleteResult = [];
        foreach ($tids as $tid) {
            $placeholders[$i] = ':tid' . $i;
            $bindValues[$i] = $tid;
            $i++;
        }// endforeach;
        unset($i, $tid);

        // get the result to use them later.
        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `tid` IN (' . implode(', ', $placeholders) . ')';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        foreach ($bindValues as $index => $value) {
            $Sth->bindValue($placeholders[$index], $value);
        }// endforeach;
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Sth);

        // delete on taxonomy_term_data, taxonomy_fields, taxonomy_index tables.
        $sql = 'DELETE taxonomy_term_data, taxonomy_fields, taxonomy_index
            FROM `' . $this->tableName . '` AS `taxonomy_term_data`
            LEFT JOIN `' . $this->Db->tableName('taxonomy_fields') . '` AS `taxonomy_fields` ON `taxonomy_term_data`.`tid` = `taxonomy_fields`.`tid`
            LEFT JOIN `' . $this->Db->tableName('taxonomy_index') . '` AS `taxonomy_index` ON `taxonomy_term_data`.`tid` = `taxonomy_index`.`tid`
            WHERE `taxonomy_term_data`.`tid` IN (' . implode(', ', $placeholders) . ')';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        foreach ($bindValues as $index => $value) {
            $Sth->bindValue($placeholders[$index], $value);
        }// endforeach;
        unset($index, $value);
        $deleteResult[] = $Sth->execute();
        $Sth->closeCursor();
        unset($Sth);

        // delete on translation_matcher table.
        $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);
        $deleteResult[] = $TranslationMatcherDb->deleteIfAllEmpty('taxonomy_term_data', $tids);
        unset($TranslationMatcherDb);

        // delete on url_aliases table.
        foreach ($result as $row) {
            $sql = 'DELETE FROM `' . $this->Db->tableName('url_aliases') . '` WHERE `alias_content_id` = :alias_content_id AND `alias_content_type` = :alias_content_type';
            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            $Sth->bindValue(':alias_content_id', $row->tid, \PDO::PARAM_INT);
            $Sth->bindValue(':alias_content_type', $row->t_type);
            $deleteResult[] = $Sth->execute();
            $Sth->closeCursor();
            unset($Sth);
        }// endforeach;
        unset($row);

        return (count(array_unique($deleteResult)) === 1 && end($deleteResult) === true);
    }// deleteItemsWithRelatedTables


    /**
     * Get a single taxonomy data (joined with related tables and url alias table) without its parent or children.
     * 
     * @param array $where The associative array where key is column name and value is its value.
     * @return mixed Return object if result was found, return `empty`, `null`, `false` if it was not found.
     */
    public function get(array $where = [])
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
     * Update a taxonomy term  data.
     * 
     * @param array $data The associative array where its key is column name and value is its value to update.
     * @param array $where The associative array where its key is column name and value is its value.
     * @return bool Return `true` on success update, `false` for otherwise.
     */
    public function update(array $data, array $where): bool
    {
        $output = $this->Db->update($this->tableName, $data, $where);

        return $output;
    }// update




}
