<?php
/* @var $Rc \FastRoute\RouteCollector */
/* @var $this \Rdb\System\Router */


$Rc->addGroup('/settings/cms', function(\FastRoute\RouteCollector $Rc) {
    // /admin/settings/cms page + REST API (settings page - get data via REST).
    $Rc->addRoute('GET', '', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Settings\\CMSAdmin\\Index:index');
    // /admin/settings/cms REST API.
    $Rc->addRoute('PATCH', '', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Settings\\CMSAdmin\\Updater:doUpdate');
    // /admin/settings/cms REST API.
    $Rc->addRoute('POST', '', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Settings\\CMSAdmin\\Updater:uploadWatermark');
});