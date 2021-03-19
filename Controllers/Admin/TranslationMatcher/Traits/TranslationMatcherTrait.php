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
