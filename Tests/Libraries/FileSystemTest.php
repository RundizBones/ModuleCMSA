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

        $fileName = 'some.file.name_1_2.jpg';
        $expect = 'some.file.name_1_2_.suffix.aftersuffix.jpg';
        $this->assertSame($expect, $this->FileSystem->addSuffixFileName($fileName, '_.suffix.aftersuffix'));

        $fileName = 'some.file.name_1_2.jpg';
        $expect = 'some.file.name_1_2.suffix.aftersuffix.jpg';
        $this->assertSame($expect, $this->FileSystem->addSuffixFileName($fileName, '.suffix.aftersuffix'));

        $fileName = 'some.file.name_1_2.last.jpg';
        $expect = 'some.file.name_1_2.last.aftersuffix.jpg';
        $this->assertSame($expect, $this->FileSystem->addSuffixFileName($fileName, '.aftersuffix'));

        $fileName = 'some.file.name_1_2.jpg';
        $expect = '/some.file.name_1_2_suffix(\.[0-9]{6}).jpg/';// _suffix.[random number 6 digits]
        $this->assertRegExp($expect, $this->FileSystem->addSuffixFileName($fileName, '_suffix', true));

        $fileName = 'some.file.name_1_2.last.jpg';
        $expect = '/some.file.name_1_2.last.suffix(\.[0-9]{6}).jpg/';// _suffix.[random number 6 digits]
        $this->assertRegExp($expect, $this->FileSystem->addSuffixFileName($fileName, '.suffix', true));

        $fileName = 'some.file.name_1_2.jpg';
        $expect = '/some.file.name_1_2_suffix.123456(\.[0-9]{6}).jpg/';// _suffix.[random number 6 digits]
        $this->assertRegExp($expect, $this->FileSystem->addSuffixFileName($fileName, '_suffix.123456', true));
    }// testAddSuffixFileName


    public function testGetBase64File()
    {
        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(MODULE_PATH . DIRECTORY_SEPARATOR . 'RdbCMSA' . DIRECTORY_SEPARATOR . 'Tests');
        $this->assertStringContainsString(';base64,', $FileSystem->getBase64File('phpunit.php'));
        $this->assertStringContainsString(';base64,', $FileSystem->getBase64File('../phpunit.php'));
        $this->assertSame('', $FileSystem->getBase64File('phpunit-not-exists.php'));
        unset($FileSystem);
    }// testGetBase64File


    public function testGetFullPathWithRoot()
    {
        $rootPath = MODULE_PATH . DIRECTORY_SEPARATOR . 'RdbCMSA' . DIRECTORY_SEPARATOR . 'Tests';
        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem($rootPath);
        $this->assertSame($rootPath . DIRECTORY_SEPARATOR . 'abc.txt', $FileSystem->getFullPathWithRoot('abc.txt'));
        $this->assertSame($rootPath . DIRECTORY_SEPARATOR . 'abc.txt', $FileSystem->getFullPathWithRoot('../abc.txt'));
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

        $fileName = '/path/to/some.file_1_2_thumb400(1).jpg';// (1) is something after suffix and random suffix so this is its original name, cannot be removed suffix.
        $expect = '/path/to/some.file_1_2_thumb400(1).jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '_thumb400'));

        $fileName = 'some.file.name_1_2_.suffix.aftersuffix.jpg';
        $expect = 'some.file.name_1_2.jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '_.suffix.aftersuffix'));

        $fileName = 'some.file.name_1_2.suffix.aftersuffix.jpg';
        $expect = 'some.file.name_1_2.jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '.suffix.aftersuffix'));

        $fileName = 'some.file.name_1_2.last.aftersuffix.jpg';
        $expect = 'some.file.name_1_2.last.jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '.aftersuffix'));

        $fileName = 'some.file.name_1_2_suffix.987354.jpg';
        $expect = 'some.file.name_1_2.jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '_suffix', true));

        $fileName = 'some.file.name_1_2_suffix.987354.546701.jpg';
        $expect = 'some.file.name_1_2_suffix.987354.546701.jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '_suffix', true));

        $fileName = 'some.file.name_1_2_suffix.jpg';
        $expect = 'some.file.name_1_2_suffix.jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '_suffix', true));

        $fileName = 'some.file.name_1_2.last.suffix.432198.jpg';
        $expect = 'some.file.name_1_2.last.jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '.suffix', true));

        $fileName = 'some.file.name_1_2_suffix.123456.465798.jpg';
        $expect = 'some.file.name_1_2.jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '_suffix.123456', true));

        $fileName = 'some.file.name_1_2_suffix.123456.465798_1.jpg';// _1 will be add when uploaded duplicated file, so this test must be the same nothing changed.
        $expect = 'some.file.name_1_2_suffix.123456.465798_1.jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '_suffix.123456', true));

        $fileName = 'some.file.name_1_2_suffix.123456.465798(1).jpg';// (1) is something after suffix and random suffix so this is its original name, cannot be removed suffix.
        $expect = 'some.file.name_1_2_suffix.123456.465798(1).jpg';
        $this->assertSame($expect, $this->FileSystem->removeSuffixFileName($fileName, '_suffix.123456', true));
    }// testRemoveSuffixFileName


}
