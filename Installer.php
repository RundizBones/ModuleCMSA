<?php
/**
 * Module Name: RundizBones CMS Admin.
 * Description: Content management system admin module.
 * Requires PHP: 7.4.0
 * Requires Modules: RdbAdmin, Languages
 * Author: Vee W.
 * Gettext Domain: rdbcmsa
 * 
 * Requirement: 
 * MySQL v8.0+, MariaDB v10.2.3+
 *  * MariaDB 10.2.2 for use `WITH RECURSIVE`
 *  * MariaDB 10.2.3 for JSON_EXTRACT, JSON_SEARCH functions.
 * 
 * @package RdbCMSA
 * @version 0.0.15dev-20240803
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
                'desc' => 'Related path from storage permanent folder of this module.',
                'value' => '',
            ],
            [
                'name' => 'rdbcmsa_watermarkAllNewUploaded',
                'desc' => 'Apply watermark on all new uploaded? 0=no, 1=yes.',
                'value' => 0,
            ],
            [
                'name' => 'rdbcmsa_watermarkPositionX',
                'desc' => 'Watermark horizontal position. Default is center.',
                'value' => 'center',
            ],
            [
                'name' => 'rdbcmsa_watermarkPositionY',
                'desc' => 'Watermark vertical position. Default is middle.',
                'value' => 'middle',
            ],
            [
                'name' => 'rdbcmsa_watermarkPositionYPadding',
                'desc' => 'Watermark padding space for vertical position. Default is 20.',
                'value' => 20,
            ],
            [
                'name' => 'rdbcmsa_watermarkPositionXPadding',
                'desc' => 'Watermark padding space for horizontal position. Default is 20.',
                'value' => 20,
            ],
            [
                'name' => 'rdbcmsa_imageMaxDimension',
                'desc' => 'Maximum image dimension on resize new uploaded file.',
                'value' => '2000x2000',
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
        $this->updateMoveUploadedWM();
        $this->updateConfigDb();
        $this->updateAlterStructure();
    }// update


    /**
     * Update table structure.
     */
    private function updateAlterStructure()
    {
        // update tables structure from Installer.sql using `alterStructure()` from framework v1.1.7+.
        try {
            if (method_exists($this->Db, 'alterStructure')) {
                $sqlString = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Installer.sql');
                $sqlString = $this->Db->removeSQLComments($sqlString);
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

                            $alterResults = $this->Db->alterStructure($eachStatement);
                            $this->Logger->write('modules/rdbcmsa/installer', 0, 'Alter results: {alterResults}', ['alterResults' => $alterResults]);
                            $this->Db->convertCharsetAndCollation($tableName, null);
                            unset($alterResults, $tableName);
                        }
                    }// endforeach;
                    unset($eachStatement);
                }// endif;
                unset($expSql);
            } else {
                throw new \Exception('The method alterStructure() from the framework v1.1.7 does not exists. Couldn\'t be update the table structure.');
            }// endif; `alterStructure` method exists
        } catch (\Exception $e) {
            $this->Logger->write('modules/rdbcmsa/installer', 3, $e->getMessage());
            throw $e;
        }
    }// updateAlterStructure


    /**
     * Update config in DB.
     */
    private function updateConfigDb()
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
    }// updateConfigDb


    /**
     * Update, move uploaded watermark to new location.
     * 
     * @todo [rdbcms] Remove this method on v.1.0
     */
    private function updateMoveUploadedWM()
    {
        $FileSystem = new \Rdb\System\Libraries\FileSystem(__DIR__);
        if ($FileSystem->isDir('uploadedWatermark')) {
            $fullPathPreviousUploadWMDir = $FileSystem->getFullPathWithRoot('uploadedWatermark');
            $FileSystem2 = new \Rdb\System\Libraries\FileSystem(STORAGE_PATH . '/permanent/Modules/RdbCMSA');
            if (!$FileSystem2->isDir('')) {
                $FileSystem2->createFolder('');
            }
            if ($FileSystem2->isDir('uploadedWatermark')) {
                $FileSystem2->deleteFolder('uploadedWatermark', true);
            }
            $fullPathThisModulePermanentStorageDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $FileSystem2->getFullPathWithRoot('')), " \n\r\t\v\x00/\\" . DIRECTORY_SEPARATOR);
            rename($fullPathPreviousUploadWMDir, $fullPathThisModulePermanentStorageDir . DIRECTORY_SEPARATOR . 'uploadedWatermark');
            unset($FileSystem, $FileSystem2);
            unset($fullPathPreviousUploadWMDir, $fullPathThisModulePermanentStorageDir);
        }
    }// updateMoveUploadedWM


}
