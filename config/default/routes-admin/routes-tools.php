<?php
/* @var $Rc \FastRoute\RouteCollector */
/* @var $this \Rdb\System\Router */


// admin routes:
// /tools/cms/xxx ----------------------------------------------------------------------------------
$Rc->addGroup('/tools/cms', function(\FastRoute\RouteCollector $Rc) {
    // url aliases management. ------------------------------------------------------------
    // /admin/tools/cms/url-aliases page + REST API (listing page - get data via REST).
    $Rc->addRoute('GET', '/url-aliases', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tools\URLAliases\\Index:index');
    // /admin/tools/cms/url-aliases/xx REST API (get a single item data).
    $Rc->addRoute('GET', '/url-aliases/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tools\URLAliases\\Index:doGetItem');

    // /admin/tools/cms/url-aliases/add page.
    $Rc->addRoute('GET', '/url-aliases/add', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tools\URLAliases\\Add:index');
    // /admin/tools/cms/url-aliases REST API (add an item).
    $Rc->addRoute('POST', '/url-aliases', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tools\URLAliases\\Add:doAdd');

    // /admin/tools/cms/url-aliases/edit[/xx] page.
    $Rc->addRoute('GET', '/url-aliases/edit[/{id:\d+}]', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tools\URLAliases\\Edit:index');
    // /admin/tools/cms/url-aliases/xx REST API (update an item).
    $Rc->addRoute('PATCH', '/url-aliases/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tools\URLAliases\\Edit:doUpdate');

    // /admin/tools/cms/url-aliases/xx REST API (delete items - use comma for multiple items).
    $Rc->addRoute('DELETE', '/url-aliases/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tools\URLAliases\\Actions:doDelete');
    // end url aliases management. -------------------------------------------------------
});// /tools/cms route group.
// end /tools/cms/xxx -----------------------------------------------------------------------------