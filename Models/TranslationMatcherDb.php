<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Models;


/**
 * Translation matcher model.
 * 
 * @since 0.0.2
 * @property-read null|array $isIdsExistsResult The result that have got from calling `isIdsExists()` method. This result can be empty array if found nothing but if `null` means that method is never called.
 * @property-read null|array $isIdsExistsButNotInTmIDResult The result that have got from calling `isIdsExistsButNotInTmID()` method. This result can be empty array if found nothing but if `null` means that method is never called.
 * @property-read string $tableName The `translation_matcher` table name.
 */
class TranslationMatcherDb extends \Rdb\System\Core\Models\BaseModel
{


    /**
     * @var array Allowed sort columns in db.
     */
    protected $allowedSort = ['tm_id', 'tm_table', 'matches'];


    /**
     * @var array Contain update debug info.
     */
    public $debugUpdate = [];


    /**
     * @var null|array The result that have got from calling `isIdsExists()` method. This result can be empty array if found nothing but if `null` means that method is never called.
     */
    protected $isIdsExistsResult;


    /**
     * @var null|array The result that have got from calling `isIdsExistsButNotInTmID()` method. This result can be empty array if found nothing but if `null` means that method is never called.
     */
    protected $isIdsExistsButNotInTmIDResult;


    /**
     * @var string The `translation_matcher` table name.
     */
    protected $tableName;


    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->tableName = $this->Db->tableName('translation_matcher');
    }// __construct


    /**
     * Magic get
     * 
     * @param string $name The property name.
     */
    public function __get(string $name)
    {
        $allowedAccessProps = ['isIdsExistsResult', 'isIdsExistsButNotInTmIDResult', 'tableName'];

        if (in_array($name, $allowedAccessProps) && property_exists($this, $name)) {
            return $this->{$name};
        }
    }// __get


    /**
     * Add translation matched.
     * 
     * @param array $data
     * @return mixed Return inserted ID if successfully inserted, return `0` (zero), or `false` if failed to insert.
     */
    public function add(array $data)
    {
        if (!is_scalar($data['matches'])) {
            $data['matches'] = json_encode($data['matches']);
        }

        $insertResult = $this->Db->insert($this->tableName, $data);

        if ($insertResult === true) {
            $tm_id = $this->Db->PDO()->lastInsertId();
            return (int) $tm_id;
        }
        return false;
    }// add


    /**
     * Add translation matcher id between source data and new data.
     * 
     * If source data is not exists then it will be use add source data and new translation data.<br>
     * If source data exists then it will be use update the existing source data with new translation data.
     * 
     * This method does not update existing data where contain the same language locale URL with input new data (`$newData`).
     * 
     * @since 0.0.14
     * @param array $fromData The source data. Value is associative array.
     * <pre>
     * array(
     *      'lang' => 'id',
     * );
     * </pre>
     *      The `lang` array key is language locale URL. The `id` is source data ID.
     * @param array $newData The new data. Value is associative array.
     * <pre>
     * array(
     *      'lang' => 'id',
     * );
     * </pre>
     *      The `lang` array key is language locale URL. The `id` is new data ID.
     * @param string $tm_table The data to use in `tm_table` column.
     * @return mixed Return inserted ID if it use `add()` method.
     * @throws \InvalidArgumentException Throw exception if argument format in invalid.
     */
    public function addUpdateWithSource(array $fromData, array $newData, string $tm_table)
    {
        // check associative array for both source and new data.
        if (array_is_list($fromData)) {
            throw new \InvalidArgumentException('The argument `$fromData` must be associative array.');
        }
        if (array_is_list($newData)) {
            throw new \InvalidArgumentException('The argument `$newData` must be associative array.');
        }

        if (is_null($this->isIdsExistsResult)) {
            // if there is no result of checking source ids not exists.
            $this->isIdsExists(array_values($fromData), $tm_table);
        }
        $searchTmSourceResult = $this->isIdsExistsResult;

        if ($this->Container->has('Config')) {
            /* @var $Config \Rdb\System\Config */
            $Config = $this->Container->get('Config');
            $Config->setModule('');
        } else {
            $Config = new \Rdb\System\Config();
        }
        $languages = $Config->get('languages', 'language', []);
        unset($Config);

        if (empty($searchTmSourceResult)) {
            // if there is nothing at all.
            // setup data for insert.
            $data = [
                'tm_table' => $tm_table,
                'matches' => [],
            ];

            // build languages array to data.
            foreach ($languages as $languageId => $languageItems) {
                $data['matches'][$languageId] = '';
                if ($languageId === key($fromData)) {
                    $data['matches'][$languageId] = $fromData[$languageId];
                } elseif ($languageId === key($newData)) {
                    $data['matches'][$languageId] = $newData[$languageId];
                }
            }// endforeach;
            unset($languageId, $languageItems, $languages);

            unset($searchTmSourceResult);
            // insert
            return $this->add($data);
        } else {
            $updated = 0;
            foreach ($searchTmSourceResult as $searchRow) {
                $jsonMatches = json_decode($searchRow->matches);

                // check and fill all missing language locale URL. ----------------------
                $languageIdsDb = array_keys(get_object_vars($jsonMatches));
                $languagesConfig = array_keys($languages);
                $missedLanguageIds = array_merge(array_diff($languageIdsDb, $languagesConfig), array_diff($languagesConfig, $languageIdsDb));
                unset($languageIdsDb, $languagesConfig);

                // found missing language locale URL, fill them.
                foreach ($missedLanguageIds as $missedLanguageId) {
                    $jsonMatches->{$missedLanguageId} = '';
                }// endforeach;
                unset($missedLanguageId, $missedLanguageIds);
                // end check and fill all missing language locale URL. ------------------

                // prepare data for update.
                $data = [
                    'matches' => [],
                ];
                foreach ($jsonMatches as $resultLanguageId => $resultDataId) {
                    $data['matches'][$resultLanguageId] = strval($resultDataId);
                    if ($resultLanguageId === key($newData) && empty($resultDataId)) {
                        // if same language locale URL and data from DB is empty.
                        $data['matches'][$resultLanguageId] = strval($newData[$resultLanguageId]);
                    }
                }// endforeach;
                unset($resultDataId, $resultLanguageId);

                // do the update data.
                $updateResult = $this->update($data, ['tm_id' => $searchRow->tm_id]);
                if (true === $updateResult) {
                    ++$updated;
                }

                unset($data, $jsonMatches, $updateResult);
            }// endforeach;
            unset($searchRow, $searchTmSourceResult);

            unset($languages);
            return $updated;
        }
    }// addUpdateWithSource


    /**
     * Delete translation matches if all data ids are empty.
     * 
     * This will be trying to remove selected data id in the table to empty and will be delete row if all data ids are empty.
     * 
     * @since 0.0.4
     * @param string $tm_table The table to work with.
     * @param array $dataIds The data ID to delete.
     * @return boolean Return `true` on success, `false` on failure.
     */
    public function deleteIfAllEmpty(string $tm_table, array $dataIds)
    {
        $result = $this->listItems([
            'where' => [
                'tm_table' => $tm_table,
            ],
            'findDataIds' => $dataIds,
        ]);

        if (!isset($result['items'])) {
            return false;
        } elseif (!is_array($result['items'])) {
            return false;
        } elseif (empty($result['items'])) {
            return true;
        }

        $deleteTmIds = [];

        foreach ($result['items'] as $row) {
            $matches = json_decode($row->matches);
            // loop to unset its value.
            foreach ($matches as $language => $dataId) {
                if (in_array($dataId, $dataIds)) {
                    // if found matched a data ID in data IDs array.
                    // unset its value.
                    if (is_array($matches)) {
                        $matches[$language] = '';
                    } elseif (is_object($matches)) {
                        $matches->{$language} = '';
                    }
                }
            }// endforeach;
            unset($dataId, $language);

            // now loop to check if it is all empty or not.
            $allEmpty = true;
            foreach ($matches as $language => $dataId) {
                if ($dataId !== '' && !is_null($dataId)) {
                    $allEmpty = false;
                    break;
                }
            }// endforeach;
            unset($dataId, $language);

            if (true === $allEmpty) {
                // if all data id are empty.
                // mark tm ids to delete it once.
                $deleteTmIds[] = (int) $row->tm_id;
            } else {
                // if all data id is not empty.
                // just update.
                $data = [];
                $data['matches'] = json_encode($matches);
                $this->update($data, ['tm_id' => $row->tm_id]);
                unset($data);
            }
            unset($matches);
        }// endforeach; $result['items'];
        unset($row);

        if (isset($deleteTmIds) && !empty($deleteTmIds)) {
            $this->deleteMultiple($deleteTmIds);
        }
        unset($deleteTmIds);

        return true;
    }// deleteIfAllEmpty


    /**
     * Delete multiple translation matcher items.
     * 
     * @param array $tm_ids The TM IDs as array.
     * @return bool Return `true` on success, `false` on failure.
     */
    public function deleteMultiple(array $tm_ids): bool
    {
        /*
         * PluginHook: Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb->deleteMultiple.beforeDelete
         * PluginHookDescription: Hook on before delete multiple translation matcher IDs.
         * PluginHookParam: <br>
         *      array $tm_ids The translation matcher IDs array.
         * PluginHookSince: 0.0.15
         */
        /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
        $Plugins = $this->Container->get('Plugins');
        $Plugins->doHook(__CLASS__.'->'.__FUNCTION__.'.beforeDelete', [$tm_ids]);
        unset($Plugins);

        $values = [];
        $tmIdsPlaceholder = [];
        $i = 0;
        foreach ($tm_ids as $tm_id) {
            $tmIdsPlaceholder[] = ':tmIdsIn' . $i;
            $values[':tmIdsIn' . $i] = $tm_id;
            $i++;
        }// endforeach;
        unset($i, $tm_id);

        // delete from `translation_matcher` table.
        $sql = 'DELETE FROM `' . $this->tableName . '` WHERE `tm_id` IN (' . implode(', ', $tmIdsPlaceholder) . ')';
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
        // end delete from `translation_matcher` table.

        if (true === $deleteResult) {
            return true;
        }
        return false;
    }// deleteMultiple


    /**
     * Get a single translation matched data.
     * 
     * @param array $where The associative array where key is column name and value is its value.<br>
     *                          Special where array keys:<br>
     *                          `findDataIds` (array) The array of data id to look in `matches`,<br>
     * @param array $options The associative array options. Available options keys:<br>
     *                          `getRelatedData` (bool) Set to `true` to get related data such as posts name for table name posts. (see `getRelatedData()` method.) Default is `false`.<br>
     * @return object|false Return object, or `false` on failure.
     * @throws \InvalidArgumentException Throw the exception if argument is invalid.
     */
    public function get(array $where, array $options = [])
    {
        if (isset($where['findDataIds']) && !is_array($where['findDataIds'])) {
            throw new \InvalidArgumentException('The argument `$where[\'formDataIds\']` must be array, ' . gettype($where['findDataIds']) . ' given.');
        }

        $sql = 'SELECT * FROM `' . $this->tableName . '` AS `translation_matcher`
            WHERE 1';

        $bindValues = [];
        $bindValuesDataType = [];

        if (array_key_exists('findDataIds', $where) && is_array($where['findDataIds']) && !empty($where['findDataIds'])) {
            if ($this->Container->has('Config')) {
                /* @var $Config \Rdb\System\Config */
                $Config = $this->Container->get('Config');
                $Config->setModule('');
            } else {
                $Config = new \Rdb\System\Config();
            }
            $languages = $Config->get('languages', 'language', []);
            unset($Config);

            if (is_array($languages)) {
                $sql .= ' AND (';
                // build placeholders.
                $placeholders = [];
                $i = 0;
                foreach ($where['findDataIds'] as $data_id) {
                    if (is_numeric($data_id)) {
                        $placeholders[] = ':data_id' . $i;
                        $bindValues[':data_id' . $i] = (int) $data_id;
                        $bindValuesDataType[':data_id' . $i] = \PDO::PARAM_INT;
                        $i++;
                    }
                }// endforeach;
                unset($data_id);

                require_once MODULE_PATH . '/RdbCMSA/Helpers/php-array.php';
                $lastArrayKey = array_key_last($languages);
                foreach ($languages as $languageId => $languageItems) {
                    $sql .= ' JSON_EXTRACT(`matches`, \'$."' . $languageId . '"\') IN (' . implode(', ', $placeholders) . ')';
                    if ($languageId !== $lastArrayKey) {
                        $sql .= ' OR ';
                    }
                }// endforeach; $languages
                unset($languageId, $languageItems, $languages, $lastArrayKey);
                $sql .= ')';
                unset($placeholders);
            }// endif; is_array $languages
        }// endif; findDataIds
        unset($where['findDataIds']);

        // generate where placeholders and values.
        $placeholders = [];

        $genWhereValues = $this->Db->buildPlaceholdersAndValues($where);
        if (isset($genWhereValues['values'])) {
            $bindValues = array_merge($bindValues, $genWhereValues['values']);
        }
        if (isset($genWhereValues['placeholders'])) {
            $placeholders = array_merge($placeholders, $genWhereValues['placeholders']);
        }
        unset($genWhereValues);

        $sql .= ' AND ' . implode(' AND ', $placeholders);
        unset($placeholders);
        $sql .= ' LIMIT 0, 1';

        $Sth = $this->Db->PDO()->prepare($sql);
        foreach ($bindValues as $placeholder => $value) {
            if (array_key_exists($placeholder, $bindValuesDataType)) {
                $Sth->bindValue($placeholder, $value, $bindValuesDataType[$placeholder]);
            } else {
                $Sth->bindValue($placeholder, $value);
            }
        }// endforeach;
        unset($bindValues, $bindValuesDataType, $placeholder, $sql, $value);

        $Sth->execute();
        $result = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($Sth);

        if (is_object($result)) {
            if (isset($options['getRelatedData']) && $options['getRelatedData'] === true) {
                $matches = json_decode($result->matches, true);
                $dataIds = array_filter(array_values($matches));

                $resultData = $this->getRelatedData($dataIds, $result->tm_table);
                if (is_array($resultData)) {
                    foreach ($resultData as $rowData) {
                        $matches['data_id' . $rowData->data_id] = [
                            'data_type' => $rowData->data_type,
                            'data_name' => $rowData->data_name,
                        ];
                    }// endforeach;
                    unset($rowData);
                }
                unset($resultData);

                $result->matches = json_encode($matches);
                unset($matches);
            }// endif $options['getRelatedData']
        }

        return $result;
    }// get


    /**
     * Get related data (from posts, taxonomy_term_data tables). 
     * The related data that will be retrieve is just simple, it contains ID, name, type for selected table.
     * 
     * @param array $dataIds The array of data IDs. If it is posts, then this will be post_id(s), or tid(s) for taxonomy.
     * @param string $tm_table The data table.
     * @return mixed Return array if found, return empty, null, false for otherwise.
     */
    protected function getRelatedData(array $dataIds, string $tm_table = 'posts')
    {
        $placeholders = [];
        $bindValues = [];
        $i = 0;
        foreach ($dataIds as $dataId) {
            $placeholders[] = ':dataId' . $i;
            $bindValues[':dataId' . $i] = $dataId;
            $i++;
        }// endforeach;
        unset($dataId, $dataIds);

        $sql = 'SELECT ';
        if (strtolower($tm_table) === 'posts') {
            $sql .= ' `posts`.*, 
                `posts`.`post_id` AS `data_id`, 
                `posts`.`post_type` AS `data_type`, 
                `posts`.`post_name` AS `data_name` ';
            $sql .= ' FROM `' . $this->Db->tableName('posts') . '` AS `posts`';
            $sql .= ' WHERE `post_id` IN (' . implode(', ', $placeholders) . ')';
        } elseif (strtolower($tm_table) === 'taxonomy_term_data') {
            $sql .= ' `taxonomy_term_data`.*, 
                `taxonomy_term_data`.`tid` AS `data_id`, 
                `taxonomy_term_data`.`t_type` AS `data_type`, 
                `taxonomy_term_data`.`t_name` AS `data_name`';
            $sql .= ' FROM `' . $this->Db->tableName('taxonomy_term_data') . '` AS `taxonomy_term_data`';
            $sql .= ' WHERE `tid` IN (' . implode(', ', $placeholders) . ')';
        }
        unset($placeholders);

        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($bindValues, $placeholder, $value);
        $Sth->execute();
        $resultData = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Sth);

        return $resultData;
    }// getRelatedData


    /**
     * Check if current language of selected id is empty.
     * 
     * @since 0.0.14
     * @param int $id The selected ID.
     * @param string $tmTable The table name in column `tm_table`.
     * @param string $currentLanguage Language to check. Leave empty to auto get current language. Default is empty string.
     * @return bool Return `true` if empty, `false` if not.
     */
    public function isCurrentLangEmpty(int $id, string $tmTable, string $currentLanguage = ''): bool
    {
        if (is_null($this->isIdsExistsResult)) {
            // if `isIdsExistsResult` is null (never check before).
            if ($this->isIdsExists([$id], $tmTable) === false) {
                // if id is not exists. this means empty, yes.
                return true;
            }
        }// endif; isIdsExistsResult is null.

        if (empty(trim($currentLanguage))) {
            $currentLanguage = ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? null);
            if (empty($currentLanguage)) {
                if ($this->Container->has('Config')) {
                    /* @var $Config \Rdb\System\Config */
                    $Config = $this->Container->get('Config');
                    $Config->setModule('');
                } else {
                    $Config = new \Rdb\System\Config();
                }
                $currentLanguage = $Config->getDefaultLanguage();
                unset($Config);
            }
        }// endif; current language argument is empty.

        $tmResults = $this->isIdsExistsResult;

        foreach ($tmResults as $tmResultRow) {
            if (isset($tmResultRow->matches)) {
                $matchesJSO = json_decode($tmResultRow->matches);
                if (is_object($matchesJSO)) {
                    foreach ($matchesJSO as $languageId => $dataId) {
                        if ($currentLanguage === $languageId && !empty($dataId)) {
                            // if current language is found in DB and its value is not empty.
                            return false;
                        }
                    }// endforeach;
                    unset($dataId, $languageId);
                }// endif; $matchesJSO is object.
                unset($matchesJSO);
            }
        }// endforeach;
        unset($tmResultRow, $tmResults);

        return true;
    }// isCurrentLangEmpty


    /**
     * Check if any of specify IDs exists on DB.
     * 
     * This will be set the searched IDs exists result in `isIdsExistsResult` class's property.
     * 
     * @since 0.0.14
     * @param array $ids The indexed array of IDs to check. Any must not exists, if one exists then it will be return `false`.
     * @param string $tm_table The table in `tm_table` column to check.
     * @return bool Return `true` if not exists, `false` if exists. If `$ids` is empty then return `false`.
     */
    public function isIdsExists(array $ids, string $tm_table): bool
    {
        if (empty($ids)) {
            $this->isIdsExistsResult = [];
            return false;
        }

        $options = [];
        $options['findDataIds'] = $ids;
        $options['where'] = [
            'tm_table' => $tm_table,
        ];
        $options['unlimited'] = true;
        $tmResult = $this->listItems($options);
        unset($options);

        if (isset($tmResult['total']) && $tmResult['total'] > 0) {
            // if found matched exists in db.
            $this->isIdsExistsResult = ($tmResult['items'] ?? []);
            return true;
        }

        $this->isIdsExistsResult = [];
        return false;
    }// isIdsExists


    /**
     * Check if any of specify IDs exists on DB but not in certain translation matcher ID.
     * 
     * This will be set the searched IDs exists result in `isIdsExistsResult` class's property.
     * 
     * @since 0.0.14
     * @param int $tm_id Translation matcher ID to exclude in search where the specified `$ids` must not in this `tm_id` row.
     * @param array $ids The indexed array of IDs to check. Any must not exists, if one exists then it will be return `false`.
     * @param string $tm_table The table in `tm_table` column to check.
     * @return bool Return `true` if not exists, `false` if exists. If `$ids` is empty then return `false`.
     */
    public function isIdsExistsButNotInTmID(int $tm_id, array $ids, string $tm_table): bool
    {
        if (empty($ids)) {
            $this->isIdsExistsButNotInTmIDResult = [];
            return false;
        }

        $options = [];
        $options['findDataIds'] = $ids;
        $options['where'] = [
            'tm_table' => $tm_table,
            'tm_id' => '!= ' . $tm_id,
        ];
        $options['unlimited'] = true;
        $tmResult = $this->listItems($options);
        unset($options);

        if (isset($tmResult['total']) && $tmResult['total'] > 0) {
            // if found matched exists in db.
            $this->isIdsExistsButNotInTmIDResult = ($tmResult['items'] ?? []);
            return true;
        }

        $this->isIdsExistsButNotInTmIDResult = [];
        return false;
    }// isIdsExistsButNotInTmID


    /**
     * List items.
     * 
     * @param array $options The associative array options. Available options keys:<br>
     *                          `search` (string) the search term,<br>
     *                          `findDataIds` (array) The array of data id to look in `matches`,<br>
     *                          `where` (array) the where conditions where key is column name and value is its value,<br>
     *                          `tmIdsIn` (array) the translation matcher IDs to look in the sql command `WHERE IN (...),<br>
     *                          `sortOrders` (array) the sort order where `sort` key is column name, `order` key is mysql order (ASC, DESC),<br>
     *                          `unlimited` (bool) set to `true` to show unlimited items, unset or set to `false` to show limited items,<br>
     *                          `limit` (int) limit items per page. maximum is 1000,<br>
     *                          `offset` (int) offset or start at record. 0 is first record,<br>
     *                          `getRelatedData` (bool) Set to `true` to get related data such as posts name for table name posts. (see `getRelatedData()` method.) Default is `false`.<br>
     * @return array Return associative array with `total` and `items` in keys.
     */
    public function listItems(array $options): array
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
        $bindValuesDataType = [];
        $output = [];
        $sql = 'SELECT %*% FROM `' . $this->tableName . '` AS `translation_matcher`
            WHERE 1';
        if (array_key_exists('search', $options) && is_scalar($options['search']) && !empty($options['search'])) {
            $sql .= ' AND (';
            $sql .= '`translation_matcher`.`tm_table` LIKE :search';
            $sql .= ' OR JSON_SEARCH(`matches`, \'all\', :search) IS NOT NULL';
            $sql .= ')';
            $bindValues[':search'] = '%' . $options['search'] . '%';
        }

        if (array_key_exists('findDataIds', $options) && is_array($options['findDataIds']) && !empty($options['findDataIds'])) {
            if ($this->Container->has('Config')) {
                /* @var $Config \Rdb\System\Config */
                $Config = $this->Container->get('Config');
                $Config->setModule('');
            } else {
                $Config = new \Rdb\System\Config();
            }
            $languages = $Config->get('languages', 'language', []);
            unset($Config);

            if (is_array($languages)) {
                $sql .= ' AND (';
                // build placeholders.
                $placeholders = [];
                $i = 0;
                foreach ($options['findDataIds'] as $data_id) {
                    if (is_numeric($data_id)) {
                        $placeholders[] = ':data_id' . $i;
                        $bindValues[':data_id' . $i] = (int) $data_id;
                        $bindValuesDataType[':data_id' . $i] = \PDO::PARAM_INT;
                        $i++;
                    }
                }// endforeach;
                unset($data_id);

                require_once MODULE_PATH . '/RdbCMSA/Helpers/php-array.php';
                $lastArrayKey = array_key_last($languages);
                foreach ($languages as $languageId => $languageItems) {
                    $sql .= ' JSON_EXTRACT(`matches`, \'$."' . $languageId . '"\') IN (' . implode(', ', $placeholders) . ')';
                    if ($languageId !== $lastArrayKey) {
                        $sql .= ' OR ';
                    }
                }// endforeach; $languages
                unset($languageId, $languageItems, $languages, $lastArrayKey);
                $sql .= ')';
                unset($placeholders);
            }// endif; is_array $languages
        }// endif; findDataIds

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

        if (array_key_exists('tmIdsIn', $options) && is_array($options['tmIdsIn']) && !empty($options['tmIdsIn'])) {
            // translation matcher IDs IN(..).
            $sql .= ' AND';

            $tmIdsInPlaceholder = [];
            $i = 0;
            foreach ($options['tmIdsIn'] as $tm_id) {
                $tmIdsInPlaceholder[] = ':tmIdsIn' . $i;
                $bindValues[':tmIdsIn' . $i] = (int) $tm_id;
                $bindValuesDataType[':tmIdsIn' . $i] = \PDO::PARAM_INT;
                $i++;
            }// endforeach;
            unset($i, $tm_id);

            $sql .= ' `translation_matcher`.`tm_id` IN (' . implode(', ', $tmIdsInPlaceholder) . ')';
            unset($tmIdsInPlaceholder);
        }

        // prepare and get 'total' records while not set limit and offset.
        $Sth = $this->Db->PDO()->prepare(str_replace('%*%', 'COUNT(DISTINCT `translation_matcher`.`tm_id`) AS `total`', $sql));
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            if (array_key_exists($placeholder, $bindValuesDataType)) {
                $Sth->bindValue($placeholder, $value, $bindValuesDataType[$placeholder]);
            } else {
                $Sth->bindValue($placeholder, $value);
            }
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
                    $orderby[] = '`translation_matcher`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
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
        $Sth = $this->Db->PDO()->prepare(
            str_replace(
                '%*%', 
                '`translation_matcher`.*', 
                $sql
            )
        );
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            if (array_key_exists($placeholder, $bindValuesDataType)) {
                $Sth->bindValue($placeholder, $value, $bindValuesDataType[$placeholder]);
            } else {
                $Sth->bindValue($placeholder, $value);
            }
        }// endforeach;
        unset($placeholder, $value);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($bindValues, $bindValuesDataType, $sql, $Sth);

        if (is_array($result) && isset($options['getRelatedData']) && $options['getRelatedData'] === true) {
            foreach ($result as $row) {
                $matches = json_decode($row->matches, true);
                $dataIds = array_filter(array_values($matches));

                $resultData = $this->getRelatedData($dataIds, $row->tm_table);
                if (is_array($resultData)) {
                    foreach ($resultData as $rowData) {
                        $matches['data_id' . $rowData->data_id] = [
                            'data_type' => $rowData->data_type,
                            'data_name' => $rowData->data_name,
                        ];
                    }// endforeach;
                    unset($rowData);
                }
                unset($resultData);

                $row->matches = json_encode($matches);
                unset($matches);
            }// endforeach;
            unset($row);
        }

        $output['items'] = $result;

        unset($result);
        return $output;
    }// listItems


    /**
     * Update the data.
     * 
     * @param array $data The data for `translation_matcher` table. The associative array where key is column name and value is its value.
     * @param array $where The associative array where its key is column name and value is its value.
     * @return bool Return `true` on success update, `false` for otherwise. Call to `debugUpdate` to see more info.
     * @throws \InvalidArgumentException if required array key is missed.
     */
    public function update(array $data, array $where): bool
    {
        if (empty($where)) {
            // if $where is empty.
            throw new \InvalidArgumentException('The $where argument is required and cannot be empty.');
        }

        if (!is_scalar($data['matches'])) {
            $data['matches'] = json_encode($data['matches']);
        }

        $updateResult = $this->Db->update($this->tableName, $data, $where);
        $this->debugUpdate['updateResult'] = $updateResult;

        if ($updateResult === true) {
            /*
             * PluginHook: Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb->update.success
             * PluginHookDescription: Hook on update translation matcher succeeded.
             * PluginHookParam: <br>
             *      array $data The associative array where key is column name and value is its value of `translation_matcher` DB table. This is the array that use for update.<br>
             *      array $where The same value as `$data` but this is the array that use for search conditions before update.
             * PluginHookSince: 0.0.15
             */
            /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
            $Plugins = $this->Container->get('Plugins');
            $Plugins->doHook(__CLASS__.'->'.__FUNCTION__.'.success', [$data, $where]);
            unset($Plugins);
            return true;
        }

        $this->debugUpdate['errorInfo'] = $this->Db->PDOStatement()->errorInfo();
        $this->debugUpdate['rowCount'] = $this->Db->PDOStatement()->rowCount();

        return false;
    }// update


}
