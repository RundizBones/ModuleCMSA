<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\TranslationMatcher\Traits;


/**
 * Translation matcher trait.
 * 
 * @since 0.0.2
 */
trait TranslationMatcherTrait
{


    /**
     * Get languages and its translation matched.
     * 
     * This method was called from controllers.
     * 
     * @since 0.0.14
     * @param int $objectId An object ID that may stored in column `matches` of `translation_matcher` table.
     * @param array $languages Languages from config file.
     * @param \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb $TranslationMatcherDb The translation matcher model class.
     * @param string $tmTable The value for column `tm_table` to check in the `translation_matcher` table. Default value is 'taxonomy_term_data'.
     * @return array Return associative array.<pre>
     * array(
     *     'th' => array(
     *         // the data in here will be available if exists only.
     *         'id' => 12,// the data ID that matched in `matches` column DB.
     *         'data' => array(
     *             'data_type' => 'xxx',// the data type of the table in `tm_table` column. Example 'category', 'tag'.
     *             'data_name' => 'xxxyyy',// the data name of the table in `tm_table` column.
     *         ),
     *     ),
     *     // ... en-US and so on
     * )
     * </pre>
     */
    protected function getLanguagesAndTranslationMatched(
        int $objectId, 
        array $languages, 
        \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb $TranslationMatcherDb,
        string $tmTable = 'taxonomy_term_data'
    ): array {
        $output = [];
        foreach ($languages as $languageId => $languageItems) {
            $TmatchResult = $TranslationMatcherDb->get(
                [
                    'findDataIds' => [$objectId], 
                    'tm_table' => $tmTable,
                ],
                [
                    'getRelatedData' => true,
                ]
            );
            $output[$languageId] = [];

            if (isset($TmatchResult->matches)) {
                $matchesJSO = json_decode($TmatchResult->matches);
                if (isset($matchesJSO->{$languageId})) {
                    $matchedTranslatedID = $matchesJSO->{$languageId};
                    if (isset($matchesJSO->{'data_id' . $matchedTranslatedID})) {
                        $matchedData = $matchesJSO->{'data_id' . $matchedTranslatedID};
                        $output[$languageId] = [
                            'id' => $matchedTranslatedID,
                            'data' => $matchedData,
                            'tm_table' => $TmatchResult->tm_table,
                        ];
                    }
                    unset($matchedData, $matchedTranslatedID);
                }
                unset($matchesJSO);
            }
            unset($TmatchResult);
        }// endforeach;
        unset($languageId, $languageItems);

        return $output;
    }// getLanguagesAndTranslationMatched


    /**
     * Get languages and its translation matched from multiple object IDs.
     * 
     * This method was called from controllers.
     * 
     * @since 0.0.14
     * @param array $objectIds Multiple object IDs that may stored in column `matches` of `translation_matcher` table.
     * @param array $languages Languages from config file.
     * @param \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb $TranslationMatcherDb The translation matcher model class.
     * @param string $tmTable The value for column `tm_table` to check in the `translation_matcher` table. Default value is 'taxonomy_term_data'.
     * @return array Return associative array.<pre>
     * array(
     *     12 => array(
     *         // 12 is each object id and its type is always integer.
     *         'th' => array(
     *             // the data in here will be available if exists only.
     *             'id' => 12,// the data ID that matched in `matches` column DB.
     *             'data' => array(
     *                 'data_type' => 'xxx',// the data type of the table in `tm_table` column. Example 'category', 'tag'.
     *                 'data_name' => 'xxxyyy',// the data name of the table in `tm_table` column.
     *             ),
     *         ),
     *         // ... en-US and so on
     *     ),
     *     // ... another object ID.
     * )
     * </pre>
     */
    protected function getLanguagesAndTranslationMatchedMultipleObjects(
        array $objectIds,
        array $languages, 
        \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb $TranslationMatcherDb,
        string $tmTable = 'taxonomy_term_data'
    ): array {
        $output = [];

        $tmatchResults = $TranslationMatcherDb->listItems([
            'findDataIds' => $objectIds,
            'where' => [
                'tm_table' => $tmTable,
            ],
            'unlimited' => true,
            'getRelatedData' => true,
        ]);
        $tmatchResults = ($tmatchResults['items'] ?? []);
        $output['rawtmatchResults'] = $tmatchResults;

        // prepare output array which contains object ids and language ids.
        foreach ($objectIds as $objectId) {
            $objectId = intval($objectId);
            $output[$objectId] = [];
            foreach ($languages as $languageId => $languageItems) {
                $output[$objectId][$languageId] = [];
            }// endforeach;
            unset($languageId, $languageItems);
        }// endforeach;
        unset($objectId);
        // end prepare output array. -------------------

        // loop the result and set into pre-built output array.
        foreach ($tmatchResults as $tmatchRow) {
            $matchesJSO = json_decode($tmatchRow->matches);
            foreach ($matchesJSO as $matchesKey => $matchesValue) {
                if (
                    is_numeric($matchesValue) && // `$matchesValue` is object id.
                    array_key_exists(intval($matchesValue), $output) // found this object id in pre-built
                ) {
                    foreach ($languages as $languageId => $languageItems) {
                        if (isset($matchesJSO->{'data_id' . $matchesJSO->{$languageId}})) {
                            $matchedObjectId = intval($matchesJSO->{$languageId});
                            $output[intval($matchesValue)][$languageId] = [
                                'id' => $matchedObjectId,
                                'data' => $matchesJSO->{'data_id' . $matchedObjectId},
                                'tm_table' => $tmatchRow->tm_table,
                            ];
                            unset($matchedObjectId);
                        }// endif;
                    }// endforeach;
                    unset($languageId, $languageItems);
                    // if found one object id that matched the pre-built 
                    // it means all other languages and their object id of each language is already set from the code above.
                    // no need to loop more `matches` column data.
                    break;
                }
            }// endforeach;
            unset($matchesKey, $matchesValue);
            unset($matchesJSO);
        }// endforeach;
        unset($tmatchRow);
        // end loop the result. -----------------------
        unset($tmatchResults);

        return $output;
    }// getLanguagesAndTranslationMatchedMultipleObjects


    /**
     * Get URLs and methods for management controller.
     * 
     * @return array
     */
    protected function getTMUrlsMethod(): array
    {
        
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);

        $output = [];

        $output['addTranslationMatchRESTUrl'] = $urlAppBased . '/admin/cms/translation-matcher';
        $output['addTranslationMatchRESTMethod'] = 'POST';

        $output['deleteTranslationMatchRESTUrlBase'] = $urlAppBased . '/admin/cms/translation-matcher';
        $output['deleteTranslationMatchRESTMethod'] = 'DELETE';

        $output['editTranslationMatchRESTUrlBase'] = $urlAppBased . '/admin/cms/translation-matcher';
        $output['editTranslationMatchRESTMethod'] = 'PATCH';

        $output['getATranslationMatchRESTUrlBase'] = $urlAppBased . '/admin/cms/translation-matcher';
        $output['getATranslationMatchRESTMethod'] = 'GET';

        $output['getTranslationMatchRESTUrl'] = $urlAppBased . '/admin/cms/translation-matcher';
        $output['getTranslationMatchRESTMethod'] = 'GET';

        $output['searchEditingTranslationMatchRESTUrl'] = $urlAppBased . '/admin/cms/translation-matcher/search-editing';
        $output['searchEditingTranslationMatchRESTMethod'] = 'GET';

        return $output;
    }// getTMUrlsMethod


}
