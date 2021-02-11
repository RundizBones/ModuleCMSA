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


    public function testGetSuffixFileName()
    {
        $fileName = '/path/to/some.file.name.txt';
        $expect = '/path/to/some.file.name_suffix.txt';
        $this->assertSame($expect, $this->FileSystem->getSuffixFileName($fileName, '_suffix'));

        $fileName = '/path/to/some.file.name_1.jpg';
        $expect = '/path/to/some.file.name_1_thumb300.jpg';
        $this->assertSame($expect, $this->FileSystem->getSuffixFileName($fileName, '_thumb300'));

        $fileName = 'some.file.name_1_2.jpg';
        $expect = 'some.file.name_1_2_thumb400.jpg';
        $this->assertSame($expect, $this->FileSystem->getSuffixFileName($fileName, '_thumb400'));
    }// testGetSuffixFileName


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
