<?php
/* @var $Rc \FastRoute\RouteCollector */
/* @var $this \Rdb\System\Router */


// admin routes. -------------------------------------------------------------------------------------
$Rc->addGroup('/admin', function(\FastRoute\RouteCollector $Rc) {
    $Rc->addGroup('/cms', function(\FastRoute\RouteCollector $Rc) {
        require 'routes-admin/routes-categories-tags.php';

        require 'routes-admin/routes-posts-pages.php';

        require 'routes-admin/routes-files.php';

        require 'routes-admin/routes-translation-matcher.php';
    });// /cms route group.

    // routes: /settings/xxx.
    require 'routes-admin/routes-settings.php';

    // routes: /tools/cms/xxx.
    require 'routes-admin/routes-tools.php';
});// /admin route group.
// end admin routes. --------------------------------------------------------------------------------
