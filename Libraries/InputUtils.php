<?php


namespace Rdb\Modules\RdbCMSA\Libraries;


/**
 * Input Utilities.
 * 
 * @since 0.0.1
 */
class InputUtils
{


    /**
     * Set empty scalar value (empty string, null) to null.
     * 
     * Example: `$data['name'] = '';` will become `$data['name'] = null;`.
     * 
     * @param array $data
     * @return array
     */
    public function setEmptyScalarToNull(array $data): array
    {
        foreach ($data as $column => $value) {
            if (is_scalar($value) && ($value === '' || is_null($value))) {
                $data[$column] = null;
            }
        }// endforeach;
        unset($column, $value);

        return $data;
    }// setEmptyScalarToNull


}
