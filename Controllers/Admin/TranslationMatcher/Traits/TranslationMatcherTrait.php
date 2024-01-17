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
     * @param object $row A result row object.
     * @param array $languages Languages from config file.
     * @param \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb $TranslationMatcherDb The translation matcher model class.
     * @param string $tmTable The value for column `tm_table` to check in the `translation_matcher` table. Default value is 'taxonomy_term_data'.
     * @param string $relatedTableColumnIdName The column ID name of related table in `tm_table` column.<br>
     *      For example: the table `taxonomy_term_data` has column ID named `tid`.<br>
     *      This value will be use with `$row` argument. Default value is 'tid'.
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
        $row, 
        array $languages, 
        \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb $TranslationMatcherDb,
        string $tmTable = 'taxonomy_term_data',
        string $relatedTableColumnIdName = 'tid'
    ): array {
        $output = [];
        foreach ($languages as $languageId => $languageItems) {
            $TmatchResult = $TranslationMatcherDb->get(
                [
                    'findDataIds' => [$row->{$relatedTableColumnIdName}], 
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
