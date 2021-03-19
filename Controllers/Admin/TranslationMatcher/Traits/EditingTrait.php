<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\TranslationMatcher\Traits;


/**
 * Translation matcher editing trait. (add, edit)
 * 
 * @since 0.0.2
 */
trait EditingTrait
{


    /**
     * Validate the form data. Also send output with form result, result message, including HTTP status if there are any errors.
     * 
     * @param array $data The data to validate.
     * @param bool $formValidated The form validated status. 
     *                      This will be change once validated wether it is failed (`false`) or succeeded (`true`).
     * @return array Return output with `formResultStatus`, `formResultMessage` keys if there are any errors. 
     *                      Also send out HTTP status if it is error. 
     *                      Return empty array if there is no errors.
     */
    protected function validateForm(array $data, bool &$formValidated): array
    {
        $output = [];

        if (empty($data['tm_table'])) {
            $formValidated = false;
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = d__('rdbcmsa', 'Please select the table.');
            http_response_code(400);
        } else {
            $formValidated = true;
        }

        if (!is_array($data['matches'])) {
            $formValidated = false;
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = d__('rdbcmsa', 'Invalid matches format.');
            http_response_code(400);
        } else {
            $empty = true;
            foreach ($data['matches'] as $languageId => $data_id) {
                if (is_numeric($data_id)) {
                    $empty = false;
                    break;
                }
            }// endforeach;
            unset($data_id, $languageId);

            if (true === $empty) {
                $formValidated = false;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = d__('rdbcmsa', 'Please enter matched data between languages.');
                http_response_code(400);
            }
        }

        return $output;
    }// validateForm


}
