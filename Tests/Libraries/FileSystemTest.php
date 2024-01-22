<?php


namespace Rdb\Modules\RdbCMSA\Tests\Libraries;


class FileSystemTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\Modules\RdbCMSA\Libraries\FileSystem
     */
    protected $FileSystem;


    /**
     * @var string Path to target test folder without trailing slash.
     */
    protected $targetTestDir;


    public function setup(): void
    {
        $this->runApp('GET', '/');// call runApp to get constant.

        $this->targetTestDir = STORAGE_PATH . '/Modules/RdbCMSA/Tests';

        if (!is_dir($this->targetTestDir)) {
            $umask = umask(0);
            $output = mkdir($this->targetTestDir, 0755, true);
            umask($umask);
        }

        $this->FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem($this->targetTestDir);
    }// setup


    public function tearDown(): void
    {
        $this->FileSystem->deleteFolder('', true);
        @rmdir($this->targetTestDir);

        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(STORAGE_PATH);
        $FileSystem->deleteFolder('Modules', true);
        unset($FileSystem);
    }// tearDown


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
        $this->assertTrue(preg_match($expect, $this->FileSystem->addSuffixFileName($fileName, '_suffix', true)) !== false);

        $fileName = 'some.file.name_1_2.last.jpg';
        $expect = '/some.file.name_1_2.last.suffix(\.[0-9]{6}).jpg/';// _suffix.[random number 6 digits]
        $this->assertTrue(preg_match($expect, $this->FileSystem->addSuffixFileName($fileName, '.suffix', true)) !== false);

        $fileName = 'some.file.name_1_2.jpg';
        $expect = '/some.file.name_1_2_suffix.123456(\.[0-9]{6}).jpg/';// _suffix.[random number 6 digits]
        $this->assertTrue(preg_match($expect, $this->FileSystem->addSuffixFileName($fileName, '_suffix.123456', true)) !== false);
    }// testAddSuffixFileName


    public function testGetAudioMetadata()
    {
        $this->assertTrue(class_exists('\\getID3'));

        $expect = [
            'channels' => null,
            'sample_rate' => null,
            'format' => null,
            'duration' => null,
        ];
        $this->assertSame($expect, $this->FileSystem->getAudioMetadata('file-not-exists.mp3'));

        $this->FileSystem->writeFile('test-file.txt', 'hello');
        $this->assertSame($expect, $this->FileSystem->getAudioMetadata('test-file.txt', ['fullPath' => false]));// test false audio.
    }// testGetAudioMetadata


    public function testGetImageMetadata()
    {
        $this->assertTrue(function_exists('getimagesize'));

        $expect = [
            'width' => null,
            'height' => null,
        ];
        $this->assertSame($expect, $this->FileSystem->getImageMetadata('file-not-exists.jpg'));

        $this->FileSystem->writeFile('test-file.txt', 'hello');
        $this->assertSame($expect, $this->FileSystem->getImageMetadata('test-file.txt', ['fullPath' => false]));// test false image.

        copy(__DIR__ . DIRECTORY_SEPARATOR . 'square-white.jpg', $this->targetTestDir . DIRECTORY_SEPARATOR . 'square-white.jpg');
        $this->assertTrue($this->FileSystem->isFile('square-white.jpg'));
        $expect = [
            'width' => 100,
            'height' => 100,
        ];
        $this->assertSame($expect, $this->FileSystem->getImageMetadata('square-white.jpg', ['fullPath' => false]));// test real image.
        $this->assertSame($expect, $this->FileSystem->getImageMetadata($this->targetTestDir . DIRECTORY_SEPARATOR . 'square-white.jpg', ['fullPath' => true]));// test real image, full path.
    }// testGetImageMetadata


    public function testGetVideoMetadata()
    {
        $this->assertTrue(class_exists('\\getID3'));

        $expect = [
            'width' => null,
            'height' => null,
            'frame_rate' => null,
            'format' => null,
            'duration' => null,
        ];
        $this->assertSame($expect, $this->FileSystem->getVideoMetadata('file-not-exists.avi'));

        $this->FileSystem->writeFile('test-file.txt', 'hello');
        $this->assertSame($expect, $this->FileSystem->getVideoMetadata('test-file.txt', ['fullPath' => false]));// test false audio.
    }// testGetVideoMetadata


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
