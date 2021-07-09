<?php
/**
 * Module Name: RundizBones CMS Admin.
 * Description: Content management system admin module.
 * Requires PHP: 7.1.0
 * Requires Modules: RdbAdmin, Languages
 * Author: Vee W.
 * Gettext Domain: rdbcmsa
 * 
 * Requirement: 
 * MySQL v8.0+, MariaDB v10.2.3+
 *  * MariaDB 10.2.2 for use `WITH RECURSIVE`
 *  * For JSON functions.
 * 
 * @package RdbCMSA
 * @version 0.0.8
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA;


/**
 * Module installer class for RdbCMSA.
 */
class Installer implements \Rdb\System\Interfaces\ModuleInstaller
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
     * @var \Rdb\System\Libraries\Logger
     */
    protected $Logger;


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;

        if ($this->Container->has('Db')) {
            $this->Db = $this->Container->get('Db');
        } else {
            $this->Db = new \Rdb\System\Libraries\Db($Container);
        }

        if ($this->Container->has('Logger')) {
            $this->Logger = $this->Container->get('Logger');
        } else {
            $this->Logger = new \Rdb\System\Libraries\Logger($Container);
        }
    }// __construct


    /**
     * Get configuration name, description, default value.
     * 
     * @return array
     */
    protected function getConfig(): array
    {
        $output = [
            [
                'name' => 'rdbcmsa_watermarkfile',
                'desc' => 'Related path from this module to watermark file.',
                'value' => '',
            ],
            [
                'name' => 'rdbcmsa_watermarkAllNewUploaded',
                'desc' => 'Apply watermark on all new uploaded? 0=no, 1=yes.',
                'value' => 0,
            ],
        ];

        return $output;
    }// getConfig


    /**
     * {@inheritDoc}
     */
    public function install()
    {
        try {
            $sqlString = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Installer.sql');
            $expSql = explode(';' . "\n", str_replace(["\r\n", "\r", "\n"], "\n", $sqlString));
            unset($sqlString);

            if (is_array($expSql)) {
                foreach ($expSql as $eachStatement) {
                    if (empty(trim($eachStatement))) {
                        continue;
                    }

                    $eachStatement = trim($eachStatement) . ';';
                    preg_match('/%\$(.[^ ]+)%/iu', $eachStatement, $matches);
                    if (isset($matches[1])) {
                        $tableName = $this->Db->tableName((string) $matches[1]);
                    }
                    unset($matches);

                    if (isset($tableName)) {
                        $eachStatement = preg_replace('/%\$(.[^ ]+)%/iu', $tableName, $eachStatement);

                        if (empty($eachStatement)) {
                            continue;
                        }

                        $this->Logger->write('modules/rdbcmsa/installer', 0, $eachStatement);

                        $Sth = $this->Db->PDO()->prepare($eachStatement);
                        $execResult = $Sth->execute();
                        $Sth->closeCursor();
                        unset($Sth);
                        if ($execResult === true) {
                            $this->Db->convertCharsetAndCollation($tableName, null);
                        }
                        unset($execResult, $tableName);
                    }
                }// endforeach;
                unset($eachStatement);
            }
            unset($expSql);

            $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
            foreach ($this->getConfig() as $config) {
                $data = [];
                $data['config_value'] = (array_key_exists('value', $config) ? $config['value'] : null);
                $data['config_description'] = (array_key_exists('desc', $config) ? $config['desc'] : null);
                if (array_key_exists('name', $config)) {
                    $ConfigDb->update($data, ['config_name' => $config['name']]);
                }
                unset($data);
            }// endforeach;
            unset($config);
        } catch (\Exception $e) {
            $this->Logger->write('modules/rdbcmsa/installer', 3, $e->getMessage());
            throw $e;
        }
    }// install


    /**
     * {@inheritDoc}
     */
    public function uninstall()
    {
        try {
            $sqlString = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Installer.sql');
            // remove comments --------------------------------------------------------------------------
            // @link https://regex101.com/r/GXb0a5/2 pattern original source code.
            $pattern = '/["\'`][^"\'`]*(?!\\\\)["\'`](*SKIP)(*F)       # Make sure we\'re not matching inside of quotes, double quotes or backticks
                |(?m-s:\s*(?:\-{2}|\#)[^\n]*$) # Single line comment
                |(?:
                  \/\*.*?\*\/                  # Multi-line comment
                  (?(?=(?m-s:[\t ]+$))         # Get trailing whitespace if any exists and only if it\'s the rest of the line
                    [\t ]+
                  )
                )/iusx';
            $sqlString = preg_replace($pattern, '', $sqlString);
            // end remove comments ---------------------------------------------------------------------

            preg_match_all('/%\$(.[^ ]+)%/miu', $sqlString, $matches);

            if (isset($matches[1]) && is_array($matches[1])) {
                $tables = array_unique($matches[1]);
                foreach ($tables as $table) {
                    $sql = 'DROP TABLE IF EXISTS `' . $this->Db->tableName($table) . '`;';

                    $this->Logger->write('modules/rdbcmsa/installer', 0, $sql);

                    $stmt = $this->Db->PDO()->prepare($sql);
                    unset($sql);
                    $stmt->execute();
                    $stmt->closeCursor();
                    unset($stmt);
                }// endforeach;
                unset($table, $tables);
            }
            unset($sqlString);

            // delete configurations.
            $sql = 'DELETE FROM `' . $this->Db->tableName('config') . '` WHERE `config_name` LIKE \'rdbcmsa_%\'';
            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            $Sth->execute();
            $Sth->closeCursor();
            unset($Sth);
        } catch (\Exception $e) {
            $this->Logger->write('modules/rdbcmsa/installer', 3, $e->getMessage());
            throw $e;
        }
    }// uninstall


    /**
     * {@inheritDoc}
     */
    public function update()
    {
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        foreach ($this->getConfig() as $config) {
            $sql = 'SELECT * FROM `' . $this->Db->tableName('config') . '` WHERE `config_name` = :config_name';
            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            $Sth->bindValue(':config_name', $config['name']);
            $Sth->execute();
            $result = $Sth->fetchObject();
            $Sth->closeCursor();
            unset($Sth);

            if (!is_object($result) || empty($result) || !isset($result->config_name)) {
                // if config is not exists.
                // insert with its default value.
                $data = [];
                $data['config_value'] = (array_key_exists('value', $config) ? $config['value'] : null);
                $data['config_description'] = (array_key_exists('desc', $config) ? $config['desc'] : null);
                if (array_key_exists('name', $config)) {
                    $ConfigDb->update($data, ['config_name' => $config['name']]);
                }
                unset($data);
            } else {
                // if config exists.
                // check that description is not empty.
                if (is_object($result) && empty($result->config_description)) {
                    // if description is empty.
                    // update the description.
                    $data = [];
                    $data['config_description'] = (array_key_exists('desc', $config) ? $config['desc'] : null);
                    $ConfigDb->update($data, ['config_name' => $config['name']]);
                    unset($data);
                }
            }

            unset($result);
        }// endforeach;
        unset($config, $ConfigDb);

        // v0.0.6 ----------------------------------
        $sql = 'ALTER TABLE `rdb_posts` ADD COLUMN IF NOT EXISTS `post_position` INT( 9 ) NOT NULL DEFAULT \'0\' COMMENT \'position when sort/order items.\' AFTER `post_status`';
        $Sth = $this->Db->PDO()->prepare($sql);
        $Sth->execute();
        $Sth->closeCursor();
        unset($sql, $Sth);
    }// update


}
