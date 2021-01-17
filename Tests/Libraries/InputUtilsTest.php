<?php


namespace Rdb\Modules\RdbCMSA\Tests\Libraries;


class InputUtilsTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\Modules\RdbCMSA\Libraries\InputUtils
     */
    protected $InputUtils;


    public function setup()
    {
        $this->InputUtils = new \Rdb\Modules\RdbCMSA\Libraries\InputUtils();
    }// setup


    public function testSetEmptyScalarToNull()
    {
        $array = [
            'name' => 'Vee',
            'lastname' => '',
            'age' => 0,
            'height' => '0',
            'address' => null,
        ];
        $assert = [
            'name' => 'Vee',
            'lastname' => null,
            'age' => 0,
            'height' => '0',
            'address' => null,
        ];
        $this->assertSame($assert, $this->InputUtils->setEmptyScalarToNull($array));
        $this->assertArraySubset($assert, $this->InputUtils->setEmptyScalarToNull($array));
    }// testSetEmptyScalarToNull


}
