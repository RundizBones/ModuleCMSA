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


    /**
     * Validate the result that have got from check exists that if input data id matched result data id or not.
     * 
     * It will be mark `$formValidated` as false and return error message if found matched.
     * 
     * @since 0.0.14
     * @param array $tmResult The result that have got from `$TranslationMatcherDb->isIdsExistsButNotInTmID()`, or `$TranslationMatcherDb->isIdsExists()` when check exists.
     * @param array $inputDataMatches The input data (usually named `matches`) that received from users.
     * @param bool $formValidated The form validated status. 
     *                      This will be change once validated wether it is failed (`false`) or succeeded (`true`).
     * @return array Return output with `formResultStatus`, `formResultMessage` keys if there are any errors. 
     *                      Also send out HTTP status if it is error. 
     *                      Return empty array if there is no errors.
     */
    protected function validateIsIdExistsLang(array $tmResult, array $inputDataMatches, bool &$formValidated): array
    {
        $output = [];

        if (isset($tmResult['items']) && is_array($tmResult['items'])) {
            foreach ($tmResult['items'] as $eachTm) {
                $jsonMatches = json_decode($eachTm->matches);
                foreach ($inputDataMatches as $inputLanguageId => $inputDataId) {
                    foreach ($jsonMatches as $resultLanguageId => $resultDataId) {
                        if ($inputDataId == $resultDataId) {
                            // if found matched exists in db.
                            $formValidated = false;

                            $output['formResultStatus'] = 'error';
                            $output['formResultMessage'][] = d__('rdbcmsa', 'The data you had entered is already exists.');
                            http_response_code(400);

                            if (defined('APP_ENV') && APP_ENV === 'development') {
                                $output['debug']['found-data_id'] = $resultDataId;
                                $output['debug']['check-duplication-matches'] = $jsonMatches;
                            }

                            break 3;
                        }
                    }// endforeach;
                    unset($resultDataId, $resultLanguageId);
                }// endforeach; $data['matches']
                unset($inputDataId, $inputLanguageId);
            }// endforeach;
            unset($eachTm);
        }// endif; translation matcher result items.

        return $output;
    }// validateIsIdExistsLang


}
