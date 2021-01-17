<?php
/* @var $Rc \FastRoute\RouteCollector */
/* @var $this \Rdb\System\Router */


// categories management. ------------------------------------------------------------
// /admin/cms/categories page + REST API (display listing page, get multiple data via REST.)
$Rc->addRoute('GET', '/categories', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Categories\\Index:index');
// /admin/cms/categories/xx REST API (get a single data.)
$Rc->addRoute('GET', '/categories/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Categories\\Index:doGetData');

// /admin/cms/categories/add page
$Rc->addRoute('GET', '/categories/add', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Categories\\Add:index');
// /admin/cms/categories REST API (add a category.)
$Rc->addRoute('POST', '/categories', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Categories\\Add:doAdd');

// /admin/cms/categories/edit/xx page
$Rc->addRoute('GET', '/categories/edit/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Categories\\Edit:index');
// /admin/cms/categories/xx REST API (edit a category.)
$Rc->addRoute('PATCH', '/categories/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Categories\\Edit:doUpdate');

// /admin/cms/categories/actions page (bulk actions confirmation).
$Rc->addRoute('GET', '/categories/actions', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Categories\\Actions:index');
// /admin/cms/categories/actions/xx REST API (other actions for multiple items - use comma for multiple items).
$Rc->addRoute('PATCH', '/categories/actions/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Categories\\Actions:doActions');
// /admin/cms/categories/xx REST API (delete categories - use comma for multiple items).
$Rc->addRoute('DELETE', '/categories/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Categories\\Actions:doDelete');
// end categories management. -------------------------------------------------------

// tags management. -------------------------------------------------------------------
// /admin/cms/tags page + REST API (listing page - get data via REST).
$Rc->addRoute('GET', '/tags', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tags\\Index:index');
// /admin/cms/tags/xx REST API (get a single item data).
$Rc->addRoute('GET', '/tags/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tags\\Index:doGetItem');

// /admin/cms/tags/add page.
$Rc->addRoute('GET', '/tags/add', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tags\\Add:index');
// /admin/cms/tags REST API (add an item).
$Rc->addRoute('POST', '/tags', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tags\\Add:doAdd');

// /admin/cms/tags/edit[/xx] page.
$Rc->addRoute('GET', '/tags/edit[/{id:\d+}]', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tags\\Edit:index');
// /admin/cms/tags/xx REST API (update an item).
$Rc->addRoute('PATCH', '/tags/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tags\\Edit:doUpdate');

// /admin/cms/tags/actions/xx REST API (other actions for multiple items - use comma for multiple items).
$Rc->addRoute('PATCH', '/tags/actions/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tags\\Actions:doActions');
// /admin/cms/tags/xx REST API (delete items - use comma for multiple items).
$Rc->addRoute('DELETE', '/tags/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Tags\\Actions:doDelete');
// end tags management. --------------------------------------------------------------