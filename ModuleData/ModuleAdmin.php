<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\ModuleData;


/**
 * The module admin class for set permissions, menu items.
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
            'RdbCMSAPosts' => ['list', 'add', 'edit', 'delete'],
            'RdbCMSAPages' => ['list', 'add', 'edit', 'delete'],
            'RdbCMSAUrlAliases' => ['list', 'add', 'edit', 'delete'],
            'RdbCMSAFiles' => ['list', 'add', 'edit', 'delete'],
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
        $keywords['RdbCMSAPosts'] = noop__('Posts');
        $keywords['RdbCMSAPages'] = noop__('Pages');
        $keywords['RdbCMSAUrlAliases'] = noop__('URL aliases');
        $keywords['RdbCMSAFiles'] = noop__('Files');

        // actions keywords
        $keywords['list'] = noop__('List items');
        $keywords['add'] = noop__('Add');
        $keywords['edit'] = noop__('Edit');
        $keywords['delete'] = noop__('Delete');

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
                'permission' => [
                    ['RdbCMSAContentCategories', 'list', 'add', 'edit', 'delete'],
                    ['RdbCMSAPosts', 'list', 'add', 'edit', 'delete'],
                ],
                'icon' => 'fas fa-file-alt fa-fw',
                'name' => d__('rdbcmsa', 'Contents'),
                'link' => $urlBaseWithLang . '/admin/rdbcmsa/posts',
                'subMenu' => [
                    0 => [
                        'id' => 'rdbcmsa-contents-posts',
                        'permission' => [
                            ['RdbCMSAPosts', 'list', 'add', 'edit', 'delete'],
                        ],
                        'name' => d__('rdbcmsa', 'Posts'),
                        'link' => $urlBaseWithLang . '/admin/rdbcmsa/posts',
                        'linksCurrent' => [
                            $urlBase . '/admin/rdbcmsa/posts/add',
                            $urlBase . '/admin/rdbcmsa/posts/edit/*',
                        ],
                    ],
                    1 => [
                        'id' => 'rdbcmsa-contents-categories',
                        'permission' => [
                            ['RdbCMSAContentCategories', 'list', 'add', 'edit', 'delete'],
                        ],
                        'name' => d__('rdbcmsa', 'Categories'),
                        'link' => $urlBaseWithLang . '/admin/rdbcmsa/categories',
                        'linksCurrent' => [
                            $urlBase . '/admin/rdbcmsa/categories/add',
                            $urlBase . '/admin/rdbcmsa/categories/edit/*',
                        ],
                    ],
                    2 => [
                        'id' => 'rdbcmsa-contents-tags',
                        'permission' => [
                            ['RdbCMSAContentTags', 'list', 'add', 'edit', 'delete'],
                        ],
                        'name' => d__('rdbcmsa', 'Tags'),
                        'link' => $urlBaseWithLang . '/admin/rdbcmsa/tags',
                        'linksCurrent' => [
                            $urlBase . '/admin/rdbcmsa/tags/add',
                            $urlBase . '/admin/rdbcmsa/tags/edit/*',
                        ],
                    ],
                    3 => [
                        'id' => 'rdbcmsa-contents-pages',
                        'permission' => [
                            ['RdbCMSAPages', 'list', 'add', 'edit', 'delete'],
                        ],
                        'name' => d__('rdbcmsa', 'Pages'),
                        'link' => $urlBaseWithLang . '/admin/rdbcmsa/pages',
                        'linksCurrent' => [
                            $urlBase . '/admin/rdbcmsa/pages/add',
                            $urlBase . '/admin/rdbcmsa/pages/edit/*',
                        ],
                    ],
                    4 => [
                        'id' => 'rdbcmsa-contents-files',
                        'permission' => [
                            ['RdbCMSAFiles', 'list', 'add', 'edit', 'delete'],
                        ],
                        'name' => d__('rdbcmsa', 'Files'),
                        'link' => $urlBaseWithLang . '/admin/rdbcmsa/files',
                        'linksCurrent' => [
                            $urlBase . '/admin/rdbcmsa/files/edit/*',
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
                        'link' => $urlBaseWithLang . '/admin/tools/rdbcmsa/url-aliases',
                        'linksCurrent' => [
                            $urlBase . '/admin/tools/rdbcmsa/url-aliases/add',
                            $urlBase . '/admin/tools/rdbcmsa/url-aliases/edit/*',
                        ],
                    ],
                ],
            ],
        ];
    }// menuItems


}
