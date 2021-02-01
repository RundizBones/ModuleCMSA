<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Categories\Traits;


/**
 * Categories trait.
 * 
 * @since 0.0.1
 */
trait CategoriesTrait
{


    /**
     * @var string Taxonomy type. Example: 'category'.
     */
    protected $taxonomyType = 'category';


    /**
     * Get current locale that can work with JS. The locale data will be match in the key `languageLocale` in framework language configuration file.
     * 
     * @return string
     */
    protected function getLocale(): string
    {
        if (isset($_SERVER['RUNDIZBONES_LANGUAGE_LOCALE'])) {
            $locales = json_decode($_SERVER['RUNDIZBONES_LANGUAGE_LOCALE'], true);
            if (is_array($locales)) {
                foreach ($locales as $locale) {
                    if (is_string($locale) && stripos($locale, '.') === false) {
                        // if locale does not contain dot. Example: th-TH.UTF-8
                        return $locale;
                    }
                }
            }
        }

        return ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? 'en-US');
    }// getLocale


    /**
     * Get URLs and methods of pages in this section.
     * 
     * @return array Return associative array.
     */
    protected function getCategoriesUrlMethods(): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);

        $output = [];

        $output['actionsCategoriesUrl'] = $urlAppBased . '/admin/cms/categories/actions';// bulk actions confirmation page.

        $output['addCategoryUrl'] = $urlAppBased . '/admin/cms/categories/add';
        $output['addCategoryRESTUrl'] = $urlAppBased . '/admin/cms/categories';
        $output['addCategoryRESTMethod'] = 'POST';

        $output['editCategoryUrlBase'] = $urlAppBased . '/admin/cms/categories/edit';
        $output['editCategoryRESTUrlBase'] = $urlAppBased . '/admin/cms/categories';
        $output['editCategoryRESTMethod'] = 'PATCH';

        $output['bulkActionsCategoryRESTUrlBase'] = $urlAppBased . '/admin/cms/categories/actions';
        $output['bulkActionsCategoryRESTMethod'] = 'PATCH';

        $output['deleteCategoryRESTUrlBase'] = $urlAppBased . '/admin/cms/categories';
        $output['deleteCategoryRESTMethod'] = 'DELETE';

        $output['getCategoriesUrl'] = $urlAppBased . '/admin/cms/categories';
        $output['getCategoriesRESTUrl'] = $urlAppBased . '/admin/cms/categories';
        $output['getCategoriesRESTMethod'] = 'GET';
        $output['getCategoryRESTUrlBase'] = $urlAppBased . '/admin/cms/categories';
        $output['getCategoryRESTMethod'] = 'GET';

        unset($Url, $urlAppBased);

        return $output;
    }// getCategoriesUrlMethods


}
