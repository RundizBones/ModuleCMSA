<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Plugins\ChangeURLs;


/**
 * Detect and change to its correct posts URLs.
 * 
 * @since 0.0.2
 */
class AdminPostsURLs
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
            $this->logChannel = 'modules/rdbcmsa/plugins/changeurls/adminpostsurls/detectadminurls';
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

        if (isset($expRemovedAppBaseUrl[0]) && strtolower($expRemovedAppBaseUrl[0]) === 'admin') {
            // if is admin page.
            if (
                isset($expRemovedAppBaseUrl[2]) &&
                (
                    strtolower($expRemovedAppBaseUrl[2]) === 'posts' ||
                    strtolower($expRemovedAppBaseUrl[2]) === 'pages'
                ) &&
                isset($expRemovedAppBaseUrl[4])
            ) {
                // if editing articles, pages.
                return $this->getTranslationMatchesForPosts(
                    $redirectUrl, 
                    $currentUrl, 
                    $configLanguageMethod, 
                    $configLanguageUrlDefaultVisible, 
                    $defaultLanguage, 
                    $languageID, 
                    $detectedLanguageID, 
                    $appBase, 
                    $expRemovedAppBaseUrl, 
                    $removedAppBaseUrl
                );
            } elseif (
                isset($expRemovedAppBaseUrl[2]) &&
                (
                    strtolower($expRemovedAppBaseUrl[2]) === 'categories' ||
                    strtolower($expRemovedAppBaseUrl[2]) === 'tags'
                ) &&
                isset($expRemovedAppBaseUrl[4])
            ) {
                // if editing categories, tags.
                return $this->getTranslationMatchesForTaxonomies(
                    $redirectUrl, 
                    $currentUrl, 
                    $configLanguageMethod, 
                    $configLanguageUrlDefaultVisible, 
                    $defaultLanguage, 
                    $languageID, 
                    $detectedLanguageID, 
                    $appBase, 
                    $expRemovedAppBaseUrl, 
                    $removedAppBaseUrl
                );
            }
        }

        unset($appBase, $expRemovedAppBaseUrl, $removedAppBaseUrl, $Url);
    }// detectAdminURLs


    /**
     * Get translation matches for posts.
     * 
     * @param string $redirectUrl
     * @param string $currentUrl
     * @param string $configLanguageMethod
     * @param bool $configLanguageUrlDefaultVisible
     * @param string $defaultLanguage
     * @param string $languageID
     * @param string $detectedLanguageID
     * @param string $appBase
     * @param array $expRemovedAppBaseUrl
     * @param string $removedAppBaseUrl
     * @return string|null
     */
    protected function getTranslationMatchesForPosts(
        $redirectUrl, 
        $currentUrl, 
        $configLanguageMethod, 
        $configLanguageUrlDefaultVisible, 
        $defaultLanguage, 
        $languageID,
        string $detectedLanguageID,
        string $appBase,
        array $expRemovedAppBaseUrl,
        string $removedAppBaseUrl
    ) {
        $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);
        $options = [];
        $options['findDataIds'] = [$expRemovedAppBaseUrl[4]];
        $options['where'] = [
            'tm_table' => 'posts',
        ];
        $options['limit'] = 1;
        $result = $TranslationMatcherDb->listItems($options);
        unset($options, $TranslationMatcherDb);

        if (isset($result['items']) && is_array($result['items'])) {
            foreach ($result['items'] as $row) {
                $matches = json_decode($row->matches);

                if (isset($matches->{$languageID})) {
                    $redirectUrl = $appBase . $detectedLanguageID . str_replace('/' . $expRemovedAppBaseUrl[4], '/' . $matches->{$languageID}, $removedAppBaseUrl);
                    if (isset($this->Logger)) {
                        $this->Logger->write(
                            $this->logChannel, 
                            0, 
                            'Replacing post_id.',
                            [
                                'redirectUrl' => $redirectUrl,
                                'currentUrl' => $currentUrl,
                                'defaultLanguage' => $defaultLanguage,
                                'languageID' => $languageID,
                                'appBase' => $appBase,
                                'originalPostId' => $expRemovedAppBaseUrl[4],
                                'replaceToPostId' => $matches->{$languageID},
                                'replacedRedirectUrl' => $redirectUrl,
                            ]
                        );
                    }
                    return $redirectUrl;
                }// endif; $matches

                unset($matches);
            }// endforeach;
            unset($row);
        }

        unset($result);
    }// getTranslationMatchesForPosts


    /**
     * Get translation matches for taxonomies.
     * 
     * @param string $redirectUrl
     * @param string $currentUrl
     * @param string $configLanguageMethod
     * @param bool $configLanguageUrlDefaultVisible
     * @param string $defaultLanguage
     * @param string $languageID
     * @param string $detectedLanguageID
     * @param string $appBase
     * @param array $expRemovedAppBaseUrl
     * @param string $removedAppBaseUrl
     * @return string|null
     */
    protected function getTranslationMatchesForTaxonomies(
        $redirectUrl, 
        $currentUrl, 
        $configLanguageMethod, 
        $configLanguageUrlDefaultVisible, 
        $defaultLanguage, 
        $languageID,
        string $detectedLanguageID,
        string $appBase,
        array $expRemovedAppBaseUrl,
        string $removedAppBaseUrl
    ) {
        $TranslationMatcherDb = new \Rdb\Modules\RdbCMSA\Models\TranslationMatcherDb($this->Container);
        $options = [];
        $options['findDataIds'] = [$expRemovedAppBaseUrl[4]];
        $options['where'] = [
            'tm_table' => 'taxonomy_term_data',
        ];
        $options['limit'] = 1;
        $result = $TranslationMatcherDb->listItems($options);
        unset($options, $TranslationMatcherDb);

        if (isset($result['items']) && is_array($result['items'])) {
            foreach ($result['items'] as $row) {
                $matches = json_decode($row->matches);

                if (isset($matches->{$languageID})) {
                    $redirectUrl = $appBase . $detectedLanguageID . str_replace('/' . $expRemovedAppBaseUrl[4], '/' . $matches->{$languageID}, $removedAppBaseUrl);
                    if (isset($this->Logger)) {
                        $this->Logger->write(
                            $this->logChannel, 
                            0, 
                            'Replacing tid.',
                            [
                                'redirectUrl' => $redirectUrl,
                                'currentUrl' => $currentUrl,
                                'defaultLanguage' => $defaultLanguage,
                                'languageID' => $languageID,
                                'appBase' => $appBase,
                                'originalTID' => $expRemovedAppBaseUrl[4],
                                'replaceToTID' => $matches->{$languageID},
                                'replacedRedirectUrl' => $redirectUrl,
                            ]
                        );
                    }
                    return $redirectUrl;
                }// endif; $matches

                unset($matches);
            }// endforeach;
            unset($row);
        }

        unset($result);
    }// getTranslationMatchesForTaxonomies


}
