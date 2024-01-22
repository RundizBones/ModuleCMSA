<?php


namespace Rdb\Modules\RdbCMSA\Tests\Models;


class UrlAliasesDbTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var string Backup SQL file.
     */
    private $backupFile = __DIR__ . DIRECTORY_SEPARATOR . 'backup_url_aliases_DELETEME.sql';


    /**
     * @var \Rdb\System\Libraries\Db
     */
    private $Db;


    /**
     * @var array DB config values with keys: `dbname`, `host`, `username`, `password`, `tablePrefix`.
     */
    protected $dbConfig = [];


    /**
     * @var \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb
     */
    protected $UrlAliasesDb;


    /**
     * Determines if a command exists on the current environment
     *
     * @link https://stackoverflow.com/a/18540185/128761
     * @param string $command The command to check
     * @return bool True if the command has been found ; otherwise, false.
     */
    private function commandExists($command)
    {
        $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';

        $process = proc_open(
                "$whereIsCommand $command",
                array(
                    0 => array("pipe", "r"), //STDIN
                    1 => array("pipe", "w"), //STDOUT
                    2 => array("pipe", "w"), //STDERR
                ),
                $pipes
        );
        if ($process !== false) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            return $stdout != '';
        }

        return false;
    }// commandExists


    /**
     * Connect to DB.
     * 
     * @param array $dbConfigVals
     * @return int Return -1 if couldn't connect.
     */
    private function connectDb(array $dbConfigVals): int
    {
        foreach ($dbConfigVals as $key => $item) {
            if (
                isset($item['dsn']) && 
                !empty($item['dsn']) &&
                isset($item['username']) &&
                !empty($item['username'])
            ) {
                // if found configured db.
                $connectResult = $this->Db->connect($key);
                if ($connectResult instanceof \PDO) {
                    $connectKey = (int) $key;
                    $this->Container['Db'] = $this->Db;
                }
                unset($connectResult);
                break;
            }
        }// endforeach;
        unset($item, $key);

        if (!isset($connectKey) || is_null($connectKey)) {
            $this->markTestIncomplete('Unable to connect to DB.');
        }

        return ($connectKey ?? -1);
    }// connectDb


    protected function setUp(): void
    {
        if (is_file($this->backupFile)) {
            $this->markTestIncomplete(
                'Backup file is already exists, could not continue. '
                . 'Please make sure that the data in `url_aliases` table are correct and then delete backup file before try again. (' . $this->backupFile . ')'
            );
        }

        $this->runApp('get', '/');
        $this->Container = $this->RdbApp->getContainer();
        $I18n = new \Rdb\System\Middleware\I18n($this->Container);
        $I18n->init();

        $this->Db = new \Rdb\System\Libraries\Db($this->Container);

        // prepare db config values.
        $Config = new \Rdb\System\Config();
        $dbConfigVals = $Config->get('ALL', 'db', []);
        if (isset($dbConfigVals[0]['dsn'])) {
            preg_match('/dbname=(.+);/i', $dbConfigVals[0]['dsn'], $matches);
            $dbname = ($matches[1] ?? '');
            unset($matches);
            if ($dbname === '') {
                $this->markTestIncomplete('Unable to get dsn in the config file.');
            }

            preg_match('/host=([0-9\.]+);/i', $dbConfigVals[0]['dsn'], $matches);
            $host = ($matches[1] ?? '');
            unset($matches);
            if ($host === '') {
                $this->markTestIncomplete('Unable to get dsn in the config file.');
            }
        } else {
            $this->markTestIncomplete('Unable to get dsn in the config file.');
        }

        if (isset($dbConfigVals[0]['username']) && isset($dbConfigVals[0]['passwd']) && isset($dbConfigVals[0]['tablePrefix'])) {
            $username = $dbConfigVals[0]['username'];
            $password = $dbConfigVals[0]['passwd'];
            $tablePrefix = $dbConfigVals[0]['tablePrefix'];
        } else {
            $this->markTestIncomplete('Unable to get database username, password, table prefix in the config file.');
        }

        $this->connectDb($dbConfigVals);
        unset($Config);
        // end preapare db config values.

        // check command exists.
        if (!$this->commandExists('mysqldump')) {
            $this->markTestIncomplete('Unable to run `mysqldump`, command is not exists.');
        }
        if (!$this->commandExists('mysql')) {
            $this->markTestIncomplete('Unable to run `mysql`, command is not exists.');
        }

        $this->dbConfig = [
            'dbname' => $dbname,
            'host' => $host,
            'username' => $username,
            'password' => $password,
            'tablePrefix' => $tablePrefix,
        ];

        // dump
        $dumpCmd = 'mysqldump -u ' . $username . ' -h ' . $host . ' -p' . $password . ' ' . $dbname . ' ' . $tablePrefix . 'url_aliases > ' . $this->backupFile;
        shell_exec($dumpCmd);

        $this->UrlAliasesDb = new \Rdb\Modules\RdbCMSA\Models\UrlAliasesDb($this->Container);
        $sql = 'TRUNCATE TABLE `' . $this->Db->tableName('url_aliases') . '`';
        $this->Db->query($sql);
    }// setUp


    protected function tearDown(): void
    {
        // restore db table from file.
        // check command exists.
        if (!$this->commandExists('mysql')) {
            $this->markTestIncomplete('Unable to run `mysql`, command is not exists.');
        }
        $restoreCmd = 'mysql -u ' . $this->dbConfig['username'] . ' -p' . $this->dbConfig['password'] . ' ' . $this->dbConfig['dbname'] . ' < ' . $this->backupFile;
        shell_exec($restoreCmd);

        // delete backup file.
        if (is_file($this->backupFile) && is_writable($this->backupFile)) {
            $deleteFileResult = @unlink($this->backupFile);

            if (!isset($deleteFileResult) || $deleteFileResult !== true) {
                $this->markTestIncomplete('Unable to delete sql file. (' . $this->backupFile . ')');
            }
        }
    }// tearDown


    public function testIsDuplicatedUrl()
    {
        // at beginning, the table should be emptied.
        $sql = 'SELECT COUNT(*) FROM `' . $this->Db->tableName('url_aliases') . '`';
        $Sth = $this->Db->PDO()->prepare($sql);
        $Sth->execute();
        $this->assertEquals(0, $Sth->fetchColumn());
        $Sth->closeCursor();
        unset($sql, $Sth);

        // there should have nothing like /abc url in the table.
        $this->assertFalse($this->UrlAliasesDb->isDuplicatedUrl('abc', ''));

        // add url
        $this->UrlAliasesDb->add([
            'alias_content_type' => 'category',
            'alias_content_id' => 1,
            'language' => $_SERVER['RUNDIZBONES_LANGUAGE'],
            'alias_url' => 'abc',
        ]);

        // now it should be duplicated.
        $this->assertTrue($this->UrlAliasesDb->isDuplicatedUrl('abc', ''));
        // other url should not duplicate
        $this->assertFalse($this->UrlAliasesDb->isDuplicatedUrl('abc/d', ''));

        // add url
        $this->UrlAliasesDb->add([
            'alias_content_type' => 'tag',
            'alias_content_id' => 1,
            'language' => $_SERVER['RUNDIZBONES_LANGUAGE'],
            'alias_url' => 'abc/d',
        ]);
        // abc/d should be duplicated now.
        $this->assertTrue($this->UrlAliasesDb->isDuplicatedUrl('abc/d', ''));

        // now check for editing. 
        // editing category id 1, the url is not change so it should not return duplicated.
        $this->assertFalse($this->UrlAliasesDb->isDuplicatedUrl('abc', '', 1, 'category'));
        // change category url to abc/d which is duplicated with tag id 1.
        $this->assertTrue($this->UrlAliasesDb->isDuplicatedUrl('abc/d', '', 1, 'category'));
        // change category url to abc/de is not duplicated
        $this->assertFalse($this->UrlAliasesDb->isDuplicatedUrl('abc/de', '', 1, 'category'));
        // change category url to def/ghi is also not duplicated
        $this->assertFalse($this->UrlAliasesDb->isDuplicatedUrl('def/ghi', '', 1, 'category'));
        // change tag url to abc which is duplicated with category id 1.
        $this->assertTrue($this->UrlAliasesDb->isDuplicatedUrl('abc', '', 1, 'tag'));
        // assume that editing tag id 222 which is not really exists.
        // it should found duplicated as the URL must not duplicate even if it is not exists on editing id.
        $this->assertTrue($this->UrlAliasesDb->isDuplicatedUrl('abc', '', 222, 'tag'));
    }// testIsDuplicatedUrl


}
