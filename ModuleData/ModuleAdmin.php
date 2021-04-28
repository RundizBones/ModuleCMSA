<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\ModuleData;


/**
 * The module admin class for set permissions, menu items.
 * 
 * @since 0.0.1
 */
class ModuleAdmin implements \Rdb\Modules\RdbAdmin\Interfaces\ModuleAdmin
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * {@inheritDoc}
     */
    public function dashboardWidgets(): array
    {
        return [];
    }// dashboardWidgets


    /**
     * {@inheritDoc}
     */
    public function definePermissions(): array
    {
        return [
            'RdbCMSAContentCategories' => ['list', 'add', 'edit', 'delete'],
            'RdbCMSAContentTags' => ['list', 'add', 'edit', 'delete'],
            'RdbCMSAEncodeDecode' => ['encode_decode'],
            'RdbCMSAPosts' => ['list', 'add', 'edit', 'delete'],
            'RdbCMSAPages' => ['list', 'add', 'edit', 'delete'],
            'RdbCMSAUrlAliases' => ['list', 'add', 'edit', 'delete'],
            'RdbCMSAFiles' => ['list', 'add', 'edit', 'delete'],
            'RdbCMSATranslationMatcher' => ['list', 'match'],
            'RdbCMSASettings' => ['update'],
        ];
    }// definePermissions


    /**
     * {@inheritDoc}
     */
    public function permissionDisplayText(string $key = '', bool $translate = false)
    {
        if ($this->Container->has('Languages')) {
            $Languages = $this->Container->get('Languages');
        } else {
            $Languages = new \Rdb\Modules\RdbAdmin\Libraries\Languages($this->Container);
        }
        $Languages->bindTextDomain(
            'rdbcmsa', 
            dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'RdbCMSA' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );

        $keywords = [];

        // pages keywords
        $keywords['RdbCMSAContentCategories'] = noop__('Categories');
        $keywords['RdbCMSAContentTags'] = noop__('Tags');
        $keywords['RdbCMSAEncodeDecode'] = noop__('Encode/Decode');
        $keywords['RdbCMSAPosts'] = noop__('Posts');
        $keywords['RdbCMSAPages'] = noop__('Pages');
        $keywords['RdbCMSAUrlAliases'] = noop__('URL aliases');
        $keywords['RdbCMSAFiles'] = noop__('Files');
        $keywords['RdbCMSATranslationMatcher'] = noop__('Translation matcher');
        $keywords['RdbCMSASettings'] = noop__('Settings');

        // actions keywords
        $keywords['list'] = noop__('List items');
        $keywords['add'] = noop__('Add');
        $keywords['edit'] = noop__('Edit');
        $keywords['delete'] = noop__('Delete');
        $keywords['encode_decode'] = noop__('Encode/Decode');
        $keywords['match'] = noop__('Match');
        $keywords['update'] = noop__('Update');

        if (!empty($key)) {
            if (array_key_exists($key, $keywords)) {
                if ($translate === false) {
                    return $keywords[$key];
                } else {
                    return d__('rdbcmsa', $keywords[$key]);
                }
            } else {
                return $key;
            }
        } else {
            return $keywords;
        }
    }// permissionDisplayText


    /**
     * {@inheritDoc}
     */
    public function menuItems(): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        // declare language object, set text domain to make sure that this is translation for your module.
        if ($this->Container->has('Languages')) {
            $Languages = $this->Container->get('Languages');
        } else {
            $Languages = new \Rdb\Modules\RdbAdmin\Libraries\Languages($this->Container);
        }
        $Languages->bindTextDomain(
            'rdbcmsa', 
            dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'RdbCMSA' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );
        $Languages->getHelpers();

        $urlBaseWithLang = $Url->getAppBasedPath(true);
        $urlBase = $Url->getAppBasedPath();

        return [
            5 => [
                'id' => 'rdbcmsa-contents-menu',
                'icon' => 'fas fa-file-alt fa-fw',
                'name' => d__('rdbcmsa', 'Contents'),
                'link' => $urlBaseWithLang . '/admin/cms/posts',
                'liAttributes' => [
                    'data-mainmenucontainer' => true,
                ],
                'subMenu' => [
                    0 => [
                        'id' => 'rdbcmsa-contents-posts',
                        'permission' => [
                            ['RdbCMSAPosts', 'list', 'add', 'edit', 'delete'],
                        ],
                        'name' => d__('rdbcmsa', 'Posts'),
                        'link' => $urlBaseWithLang . '/admin/cms/posts',
                        'linksCurrent' => [
                            $urlBase . '/admin/cms/posts/add',
                            $urlBase . '/admin/cms/posts/edit/*',
                        ],
                    ],
                    1 => [
                        'id' => 'rdbcmsa-contents-categories',
                        'permission' => [
                            ['RdbCMSAContentCategories', 'list', 'add', 'edit', 'delete'],
                        ],
                        'name' => d__('rdbcmsa', 'Categories'),
                        'link' => $urlBaseWithLang . '/admin/cms/categories',
                        'linksCurrent' => [
                            $urlBase . '/admin/cms/categories/add',
                            $urlBase . '/admin/cms/categories/edit/*',
                        ],
                    ],
                    2 => [
                        'id' => 'rdbcmsa-contents-tags',
                        'permission' => [
                            ['RdbCMSAContentTags', 'list', 'add', 'edit', 'delete'],
                        ],
                        'name' => d__('rdbcmsa', 'Tags'),
                        'link' => $urlBaseWithLang . '/admin/cms/tags',
                        'linksCurrent' => [
                            $urlBase . '/admin/cms/tags/add',
                            $urlBase . '/admin/cms/tags/edit/*',
                        ],
                    ],
                    3 => [
                        'id' => 'rdbcmsa-contents-pages',
                        'permission' => [
                            ['RdbCMSAPages', 'list', 'add', 'edit', 'delete'],
                        ],
                        'name' => d__('rdbcmsa', 'Pages'),
                        'link' => $urlBaseWithLang . '/admin/cms/pages',
                        'linksCurrent' => [
                            $urlBase . '/admin/cms/pages/add',
                            $urlBase . '/admin/cms/pages/edit/*',
                        ],
                    ],
                    4 => [
                        'id' => 'rdbcmsa-contents-files',
                        'permission' => [
                            ['RdbCMSAFiles', 'list', 'add', 'edit', 'delete'],
                        ],
                        'name' => d__('rdbcmsa', 'Files'),
                        'link' => $urlBaseWithLang . '/admin/cms/files',
                        'linksCurrent' => [
                            $urlBase . '/admin/cms/files/edit/*',
                            $urlBase . '/admin/cms/files/scan-unindexed',
                        ],
                    ],
                    5 => [
                        'id' => 'rdbcmsa-contents-translationmatcher-divider1',
                        'permission' => [
                            ['RdbCMSATranslationMatcher', 'list', 'match'],
                        ],
                        'name' => '',
                        'link' => '#',
                        'aAttributes' => [
                            'onclick' => 'return false;',
                        ],
                        'liAttributes' => [
                            'class' => 'divider',
                        ],
                    ],
                    6 => [
                        'id' => 'rdbcmsa-translationmatcher',
                        'permission' => [
                            ['RdbCMSATranslationMatcher', 'list', 'match'],
                        ],
                        'name' => d__('rdbcmsa', 'Translation matcher'),
                        'link' => $urlBaseWithLang . '/admin/cms/translation-matcher',
                        'linksCurrent' => [
                            $urlBase . '/admin/cms/translation-matcher/*',
                        ],
                    ],
                ],
            ],
            103 => [
                'subMenu' => [
                    1 => [
                        'id' => 'rdbcmsa-url-aliases',
                        'permission' => [
                            ['RdbCMSAUrlAliases', 'list', 'add', 'edit', 'delete'],
                        ],
                        'name' => d__('rdbcmsa', 'URL aliases'),
                        'link' => $urlBaseWithLang . '/admin/tools/cms/url-aliases',
                        'linksCurrent' => [
                            $urlBase . '/admin/tools/cms/url-aliases/add',
                            $urlBase . '/admin/tools/cms/url-aliases/edit/*',
                        ],
                    ],
                    10 => [
                        'id' => 'rdbcmsa-tools-encodedecode',
                        'permission' => [
                            ['RdbCMSAEncodeDecode', 'encode_decode'],
                        ],
                        'name' => d__('rdbcmsa', 'Encode/Decode'),
                        'link' => $urlBaseWithLang . '/admin/tools/encode-decode',
                    ],
                ],
            ],// end tools menu
        ];
    }// menuItems


}
