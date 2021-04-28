<?php


namespace Rdb\Modules\RdbCMSA\Tests\Libraries;


class FileSystemTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\Modules\RdbCMSA\Libraries\FileSystem
     */
    protected $FileSystem;


    public function setup()
    {
        $this->runApp('GET', '/');
        $this->FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(STORAGE_PATH . '/Modules/RdbCMSA/Tests');
    }// setup


    public function testAddSuffixFileName()
    {
        $fileName = '/path/to/some.file.name.txt';
        $expect = '/path/to/some.file.name_suffix.txt';
        $this->assertSame($expect, $this->FileSystem->addSuffixFileName($fileName, '_suffix'));

        $fileName = '/path/to/some.file.name_1.jpg';
        $expect = '/path/to/some.file.name_1_thumb300.jpg';
        $this->assertSame($expect, $this->FileSystem->addSuffixFileName($fileName, '_thumb300'));

        $fileName = 'some.file.name_1_2.jpg';
        $expect = 'some.file.name_1_2_thumb400.jpg';
        $this->assertSame($expect, $this->FileSystem->addSuffixFileName($fileName, '_thumb400'));
    }// testAddSuffixFileName


    public function testGetBase64File()
    {
        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(MODULE_PATH . DIRECTORY_SEPARATOR . 'RdbCMSA' . DIRECTORY_SEPARATOR . 'Tests');
        $this->assertStringContainsString(';base64,', $FileSystem->getBase64File('phpunit.php'));
        $this->assertSame('', $FileSystem->getBase64File('phpunit-not-exists.php'));
        unset($FileSystem);
    }// testGetBase64File


    public function testGetFullPathWithRoot()
    {
        $rootPath = MODULE_PATH . DIRECTORY_SEPARATOR . 'RdbCMSA' . DIRECTORY_SEPARATOR . 'Tests';
        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem($rootPath);
        $this->assertSame($rootPath . DIRECTORY_SEPARATOR . 'abc.txt', $FileSystem->getFullPathWithRoot('abc.txt'));
        unset($FileSystem, $rootPath);
    }// testGetFullPathWithRoot


    public function testRemoveSuffixFileName()
    {
        $fileName = '/path/to/some.file_thumb300.jpg';
        $expect = '/path/to/some.file.jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '_thumb300'));

        $fileName = '/path/to/some.file_thumb300_thumb300.jpg';
        $expect = '/path/to/some.file_thumb300.jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '_thumb300'));

        $fileName = '/path/to/some.file_1_2_thumb400.jpg';
        $expect = '/path/to/some.file_1_2.jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '_thumb400'));
    }// testRemoveSuffixFileName


}
