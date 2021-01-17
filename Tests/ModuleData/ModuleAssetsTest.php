<?php


namespace Rdb\Modules\RdbCMSA\Tests\ModuleData;


class ModuleAssetsTest extends \Rdb\Tests\BaseTestCase
{


    protected function setUp()
    {
        $this->runApp('get', '/');
        $this->Container = $this->RdbApp->getContainer();
        $I18n = new \Rdb\System\Middleware\I18n($this->Container);
        $I18n->init();
    }// setup


    public function testAssetsMustExists()
    {
        $ModuleAssets = new \Rdb\Modules\RdbCMSA\ModuleData\ModuleAssets($this->Container);

        $assetTypes = ['css', 'js'];
        $moduleAssets = $ModuleAssets->getModuleAssets();

        foreach ($assetTypes as $assetType) {
            if (isset($moduleAssets[$assetType])) {
                foreach ($moduleAssets[$assetType] as $asset) {
                    if (isset($asset['file']) && stripos($asset['file'], '://') === false && stripos($asset['file'], '//') !== 0) {
                        // if the asset file is local.
                        // the code below was copied from `Assets::addAsset()` method.
                        $Url = new \Rdb\System\Libraries\Url($this->Container);
                        $fileRealPath = preg_replace('#^' . preg_quote($Url->getAppBasedPath()) . '#', PUBLIC_PATH . '/', $asset['file'], 1);
                        $fileRealPath = realpath($fileRealPath);
                        if (!is_string($fileRealPath)) {
                            $fileRealPath = $asset['file'];
                        }
                        $this->assertFileExists($fileRealPath);
                    } else {
                        // if the asset file is URL.
                        // just skip it.
                        $this->markTestSkipped('The file ' . ($asset['file'] ?? '(unknown `file`)') . ' was URL and couldn\'t be check for file exists.');
                    }
                }// endforeach;
                unset($asset);
            }
        }// endforeach;
        unset($assetType, $assetTypes, $moduleAssets);

        unset($ModuleAssets);
    }// testAssetsMustExists


}
