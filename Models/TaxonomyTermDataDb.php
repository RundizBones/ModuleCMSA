<?php


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
