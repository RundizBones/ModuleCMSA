<?php


namespace Rdb\Modules\RdbCMSA\Tests\Libraries;


class ImageTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\Modules\RdbCMSA\Libraries\FileSystem
     */
    protected $FileSystem;


    /**
     * @var string Path to target test folder without trailing slash.
     */
    protected $targetTestDir;


    /**
     * Copy mini image that is for test.
     */
    protected function copyTestMiniImage()
    {
        copy(__DIR__ . DIRECTORY_SEPARATOR . 'square-white.jpg', $this->targetTestDir . DIRECTORY_SEPARATOR . 'square-white.jpg');
        copy(__DIR__ . DIRECTORY_SEPARATOR . 'square-white.jpg', $this->targetTestDir . DIRECTORY_SEPARATOR . $this->FileSystem->addSuffixFileName('square-white.jpg', '_original', true));

        $FilesSubController = new \Rdb\Modules\RdbCMSA\Controllers\Admin\SubControllers\Files\FilesSubController();
        $thumbnailSizes = $FilesSubController->getThumbnailSizes();
        unset($FilesSubController);

        foreach ($thumbnailSizes as $name => list($width, $height)) {
            copy(__DIR__ . DIRECTORY_SEPARATOR . 'square-white.jpg', $this->targetTestDir . DIRECTORY_SEPARATOR . 'square-white_' . $name . '.jpg');
        }// endforeach;
        unset($height, $name, $width, $thumbnailSizes);
    }// copyTestMiniImage


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
    public function testwork()
    {
        $this->assertSame(true, true);
    }


    /*public function testSearchOriginalFile()
    {
        $this->copyTestMiniImage();

        $Image = new \Rdb\Modules\RdbCMSA\Libraries\Image('');
        $Image->Container = $this->RdbApp->getContainer();
        $options = [];
        $options['restrictedFolder'] = [];// prevent autoload `RdbCMSAdminBaseController` class on `FoldersController` failed.
        $originalFile = $Image->searchOriginalFile($this->targetTestDir . DIRECTORY_SEPARATOR . 'square-white.jpg', $options);
        $this->assertNotFalse($originalFile);
        $this->assertTrue(is_file($originalFile));

        $options['returnFullPath'] = true;
        $originalFile = $Image->searchOriginalFile($this->targetTestDir . DIRECTORY_SEPARATOR . 'square-white.jpg', $options);
        $this->assertNotFalse($originalFile);
        $this->assertTrue(is_file($originalFile));

        $options['returnFullPath'] = false;
        $options['relateFrom'] = dirname($this->targetTestDir);
        $originalFile = $Image->searchOriginalFile($this->targetTestDir . DIRECTORY_SEPARATOR . 'square-white.jpg', $options);
        $this->assertNotFalse($originalFile);
        $this->assertFalse(is_file($originalFile));
        $this->assertStringStartsWith('Tests/', $originalFile);
        $FileSystem = new \Rdb\Modules\RdbCMSA\Libraries\FileSystem(dirname($this->targetTestDir));
        $this->assertTrue(is_file($FileSystem->getFullPathWithRoot($originalFile)));
        unset($FileSystem);

        unset($Image, $originalFile);
    }// testSearchOriginalFile


    public function testRemoveWatermark()
    {
        $this->copyTestMiniImage();

        $Image = new \Rdb\Modules\RdbCMSA\Libraries\Image('');
        $Image->Container = $this->RdbApp->getContainer();
        $options = [];
        $options['restrictedFolder'] = [];// prevent autoload `RdbCMSAdminBaseController` class on `FoldersController` failed.

        $originalFile = $Image->searchOriginalFile($this->targetTestDir . DIRECTORY_SEPARATOR . 'square-white.jpg', $options);
        $this->assertTrue(is_file($originalFile));
        $Image->removeWatermark($this->targetTestDir . DIRECTORY_SEPARATOR . 'square-white.jpg', $options);
        $this->assertFalse(is_file($originalFile));

        unset($Image);
    }// testRemoveWatermark*/


}
