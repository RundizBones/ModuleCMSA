<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Plugins\ChangeURLs;


/**
 * Detect and change to its correct content URLs.
 * 
 * @since 0.0.3
 */
class AdminContentURLs
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var string
     */
    protected $logChannel = '';


    /**
     * @var \Rdb\System\Libraries\Logger
     */
    protected $Logger;


    /**
     * Class constructor.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Detect admin URLs and change to the correct URL using translation matcher.
     * 
     * @param string $redirectUrl
     * @param string $currentUrl
     * @param string $configLanguageMethod
     * @param bool $configLanguageUrlDefaultVisible
     * @param string $defaultLanguage
     * @param string $languageID
     * @return string
     */
    public function detectAdminURLs(
        $redirectUrl, 
        $currentUrl, 
        $configLanguageMethod, 
        $configLanguageUrlDefaultVisible, 
        $defaultLanguage, 
        $languageID
    ) {
        if ($this->Container->has('Logger')) {
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $this->Logger = $this->Container->get('Logger');
            $this->logChannel = 'modules/rdbcmsa/plugins/changeurls/admincontenturls/detectadminurls';
            $this->Logger->write($this->logChannel, 0, 'Change URLs plugin is working.');
        }

        if (stripos($redirectUrl, '/admin') === false) {
            // if not in admin page.
            $this->Logger->write($this->logChannel, 0, 'This is not in admin page, skipping', ['redirectUrl' => $redirectUrl]);
            return $redirectUrl;
        }

        require_once MODULE_PATH . DIRECTORY_SEPARATOR . 'Languages' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . 'multibyte.php';

        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $appBase = $Url->getAppBasedPath() . '/';
        $removedAppBaseUrl = mb_substr_replace($redirectUrl, '', 0, mb_strlen($appBase));
        $detectedLanguageID = '';
        if (stripos($removedAppBaseUrl, $languageID . '/') === 0) {
            // if still found language-id/ in the URL.
            $detectedLanguageID = $languageID . '/';
            $removedAppBaseUrl = mb_substr_replace($removedAppBaseUrl, '', 0, mb_strlen($detectedLanguageID));
        }
        $expRemovedAppBaseUrl = explode('/', $removedAppBaseUrl);

        $urlSegmentPostTypes = ['posts', 'pages'];
        $urlSegmentTaxonomyTypes = ['categories', 'tags'];

        $this->runPluginModifyUrlSegmentPostTaxonomy($urlSegmentPostTypes, $urlSegmentTaxonomyTypes);

        if (isset($expRemovedAppBaseUrl[0]) && strtolower($expRemovedAppBaseUrl[0]) === 'admin') {
            // if is admin page.
            if (array_key_exists(4, $expRemovedAppBaseUrl)) {
                // if found data_id in 5th segment.
                $dataID = $expRemovedAppBaseUrl[4];
            }

            if (
                isset($expRemovedAppBaseUrl[2]) &&
                in_array(strtolower($expRemovedAppBaseUrl[2]), $urlSegmentPostTypes)
            ) {
                // if editing articles, pages or anything that is using posts table.
                // get translation matched in posts.
                $tmTable = 'posts';
            } elseif (
                isset($expRemovedAppBaseUrl[2]) &&
                in_array(strtolower($expRemovedAppBaseUrl[2]), $urlSegmentTaxonomyTypes)
            ) {
                // if editing categories, tags.
                // get translation matched in taxonomy_term_data.
                $tmTable = 'taxonomy_term_data';
            }// endif check 3rd segment is posts or taxonomies.

            unset($urlSegmentPostTypes, $urlSegmentTaxonomyTypes);

            if (isset($tmTable) && isset($dataID)) {
                // if it was be able to detected content ID (data ID) properly.
                return $this->getTranslationMatchesForContents(
                    $redirectUrl, 
                    $currentUrl, 
                    $defaultLanguage, 
                    $languageID,
                    $detectedLanguageID,
                    $appBase,
                    $dataID,
                    $tmTable,
                    $removedAppBaseUrl
                );
            }
        }// endif; is admin page.

        unset($urlSegmentPostTypes, $urlSegmentTaxonomyTypes);
        unset($appBase, $expRemovedAppBaseUrl, $removedAppBaseUrl, $Url);
    }// detectAdminURLs


    /**
     * Get translation matches for contents.
     * 
     * @param string $redirectUrl
     * @param string $currentUrl
     * @param string $defaultLanguage
     * @param string $languageID
     * @param string $detectedLanguageID
     * @param string $appBase
     * @param int $dataID
     * @param string $tmTable
     * @param string $removedAppBaseUrl
     * @return string|null
     */
    protected function getTranslationMatchesForContents(
        $redirectUrl, 
        $currentUrl, 
        $defaultLanguage, 
        $languageID,
        string $detectedLanguageID,
        string $appBase,
        int $dataID,
        string $tmTable,
        string $removedAppBaseUrl
    ) {
        $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);
        $where = [];
        $where['findDataIds'] = [$dataID];
        $where['tm_table'] = $tmTable;
        $result = $TranslationMatcherDb->get($where);
        unset($TranslationMatcherDb, $where);

        if (isset($result) && is_object($result) && !empty($result)) {
            $matches = json_decode($result->matches);

            if (isset($matches->{$languageID})) {
                $redirectUrl = $appBase . $detectedLanguageID . str_replace('/' . $dataID, '/' . $matches->{$languageID}, $removedAppBaseUrl);

                if (isset($this->Logger)) {
                    $this->Logger->write(
                        $this->logChannel, 
                        0, 
                        'Replacing content ID.',
                        [
                            'redirectUrl' => $redirectUrl,
                            'currentUrl' => $currentUrl,
                            'defaultLanguage' => $defaultLanguage,
                            'languageID' => $languageID,
                            'appBase' => $appBase,
                            'removedAppBaseURL' => $removedAppBaseUrl,
                            'originalDataId' => $dataID,
                            'replaceToDataId' => $matches->{$languageID},
                            'replacedRedirectUrl' => $redirectUrl,
                        ]
                    );
                }

                return $redirectUrl;
            }// endif; $matches

            unset($matches);
        }

        unset($result);
    }// getTranslationMatchesForContents


    /**
     * Run the plugins to modify URL segment for post, taxonomy.
     * 
     * @param array $urlSegmentPostTypes
     * @param array $urlSegmentTaxonomyTypes
     */
    protected function runPluginModifyUrlSegmentPostTaxonomy(array &$urlSegmentPostTypes, array &$urlSegmentTaxonomyTypes)
    {
        if ($this->Container->has('Plugins')) {
            /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
            $Plugins = $this->Container->get('Plugins');

            /*
             * PluginHook: Rdb\Modules\RdbCMSA\Plugins\ChangeURLs\AdminContentURLs->detectAdminURLs.urlSegmentPostTypes
             * PluginHookDescription: Retrieve additional URL segment of editing post types that uses `posts` table. Example for pages: `admin/cms/pages/edit/nnn` so, `pages` is in here.
             * PluginHookParam: 
             *              array $urlSegmentPostTypes contain 2D array of available post types: `posts`, `pages`.
             * PluginHookReturn: Expect return array.
             * PluginHookSince: 0.0.3
             */
            $returnSegments = $Plugins->doHook(
                __CLASS__ . '->detectAdminURLs.urlSegmentPostTypes',
                [$urlSegmentPostTypes]
            );

            if (isset($this->Logger)) {
                $this->Logger->write($this->logChannel, 0, 'Returned URL segments for post types: {segments}', ['segments' => $returnSegments, 'original_segments' => $urlSegmentPostTypes]);
            }

            if (is_array($returnSegments) && !empty($returnSegments) && $returnSegments !== [$urlSegmentPostTypes]) {
                foreach ($returnSegments as $eachReturnSegment) {
                    if (!empty($eachReturnSegment)) {
                        $urlSegmentPostTypes[] = $eachReturnSegment;
                    }
                }// endforeach;
                unset($eachReturnSegment);

                $urlSegmentPostTypes = array_unique($urlSegmentPostTypes);

                if (isset($this->Logger)) {
                    $this->Logger->write($this->logChannel, 0, 'Modified URL segments for post types: {segments}', ['segments' => $urlSegmentPostTypes]);
                }
            }
            unset($returnSegments);

            /*
             * PluginHook: Rdb\Modules\RdbCMSA\Plugins\ChangeURLs\AdminContentURLs->detectAdminURLs.urlSegmentTaxonomyTypes
             * PluginHookDescription: Retrieve additional URL segment of editing taxonomy types that uses `taxonomy_term_data` table. Example for pages: `admin/cms/categories/edit/nnn` so, `categories` is in here.
             * PluginHookParam: 
             *              array $urlSegmentTaxonomyTypes contain 2D array of available taxonomy types: `categories`, `tags`.
             * PluginHookReturn: Expect return array.
             * PluginHookSince: 0.0.3
             */
            $returnSegments = $Plugins->doHook(
                __CLASS__ . '->detectAdminURLs.urlSegmentTaxonomyTypes',
                [$urlSegmentTaxonomyTypes]
            );

            if (isset($this->Logger)) {
                $this->Logger->write($this->logChannel, 0, 'Returned URL segments for taxonomy types: {segments}', ['segments' => $returnSegments, 'original_segments' => $urlSegmentTaxonomyTypes]);
            }

            if (is_array($returnSegments) && !empty($returnSegments) && $returnSegments !== [$urlSegmentTaxonomyTypes]) {
                foreach ($returnSegments as $eachReturnSegment) {
                    if (!empty($eachReturnSegment)) {
                        $urlSegmentTaxonomyTypes[] = $eachReturnSegment;
                    }
                }// endforeach;
                unset($eachReturnSegment);

                $urlSegmentTaxonomyTypes = array_unique($urlSegmentTaxonomyTypes);

                if (isset($this->Logger)) {
                    $this->Logger->write($this->logChannel, 0, 'Modified URL segments for taxonomy types: {segments}', ['segments' => $urlSegmentTaxonomyTypes]);
                }
            }
            unset($returnSegments);

            unset($Plugins);
        }// endif; container has plugins class.
    }// runPluginModifyUrlSegmentPostTaxonomy


}
