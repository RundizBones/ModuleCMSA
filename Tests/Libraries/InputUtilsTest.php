<?php


namespace Rdb\Modules\RdbCMSA\Tests\Libraries;


class InputUtilsTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\Modules\RdbCMSA\Libraries\InputUtils
     */
    protected $InputUtils;


    public function setup(): void
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
        $result = $this->InputUtils->setEmptyScalarToNull($array);
        $this->assertSame($assert, $result);
        $this->assertTrue(
            empty(array_diff_key($assert, $result)) && empty(array_diff_key($result, $assert))
        );
    }// testSetEmptyScalarToNull


}
