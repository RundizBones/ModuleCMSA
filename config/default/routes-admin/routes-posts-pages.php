<?php
/* @var $Rc \FastRoute\RouteCollector */
/* @var $this \Rdb\System\Router */


// posts management. ------------------------------------------------------------------
// /admin/cms/posts page + REST API (listing page - get data via REST).
$Rc->addRoute('GET', '/posts', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Index:index');
// /admin/cms/posts/filters REST API
$Rc->addRoute('GET', '/posts/filters', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Index:doGetFilters');
// /admin/cms/posts/xx REST API (get a single item data).
$Rc->addRoute('GET', '/posts/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Index:doGetItem');

// /admin/cms/posts/related-data page (get related data such as statuses, categories).
$Rc->addRoute('GET', '/posts/related-data', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Editing:doGetRelatedData');

// /admin/cms/posts/add page.
$Rc->addRoute('GET', '/posts/add', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Add:index');
// /admin/cms/posts REST API (add an item).
$Rc->addRoute('POST', '/posts', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Add:doAdd');

// /admin/cms/posts/edit[/xx] page.
$Rc->addRoute('GET', '/posts/edit[/{id:\d+}]', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Edit:index');
// /admin/cms/posts/xx REST API (update an item).
$Rc->addRoute('PATCH', '/posts/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Edit:doUpdate');

// revision routes. -------------------------
// /admin/cms/posts/revisions/xx REST API (get all revision history for selected post ID).
$Rc->addRoute('GET', '/posts/revisions/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Revision:index');
// /admin/cms/posts/revisions/xx/xx REST API (get a single revision content from selected post ID and revision ID).
$Rc->addRoute('GET', '/posts/revisions/{post_id:\d+}/{revision_id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Revision:doGetRevision');
// /admin/cms/posts/rollback-revision/xx/xx REST API.
$Rc->addRoute('PUT', '/posts/rollback-revision/{post_id:\d+}/{revision_id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Revision:doRollback');
// /admin/cms/posts/revisions/xx REST API (delete revision items).
$Rc->addRoute('DELETE', '/posts/revisions/{post_id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Revision:doDelete');
// end revision routes. --------------------

// /admin/cms/posts/actions/xx REST API (other actions for multiple items - use comma for multiple items).
$Rc->addRoute('PATCH', '/posts/actions/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Actions:doActions');
// /admin/cms/posts/actions/xx REST API (delete items - use comma for multiple items).
$Rc->addRoute('DELETE', '/posts/actions/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Posts\\Actions:doDelete');
// end posts management. -------------------------------------------------------------

// pages management. -----------------------------------------------------------------
// /admin/cms/pages page + REST API (listing page - get data via REST).
$Rc->addRoute('GET', '/pages', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Index:index');
// /admin/cms/pages/filters REST API
$Rc->addRoute('GET', '/pages/filters', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Index:doGetFilters');
// /admin/cms/pages/xx REST API (get a single item data).
$Rc->addRoute('GET', '/pages/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Index:doGetItem');

// /admin/cms/pages/related-data page (get related data such as statuses).
$Rc->addRoute('GET', '/pages/related-data', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Editing:doGetRelatedData');

// /admin/cms/pages/add page.
$Rc->addRoute('GET', '/pages/add', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Add:index');
// /admin/cms/pages REST API (add an item).
$Rc->addRoute('POST', '/pages', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Add:doAdd');

// /admin/cms/pages/edit[/xx] page.
$Rc->addRoute('GET', '/pages/edit[/{id:\d+}]', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Edit:index');
// /admin/cms/pages/xx REST API (update an item).
$Rc->addRoute('PATCH', '/pages/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Edit:doUpdate');

// revision routes. -------------------------
// /admin/cms/pages/revisions/xx REST API (get all revision history for selected post ID).
$Rc->addRoute('GET', '/pages/revisions/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Revision:index');
// /admin/cms/pages/revisions/xx/xx REST API (get a single revision content from selected post ID and revision ID).
$Rc->addRoute('GET', '/pages/revisions/{post_id:\d+}/{revision_id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Revision:doGetRevision');
// /admin/cms/pages/rollback-revision/xx/xx REST API.
$Rc->addRoute('PUT', '/pages/rollback-revision/{post_id:\d+}/{revision_id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Revision:doRollback');
// /admin/cms/pages/revisions/xx REST API (delete revision items).
$Rc->addRoute('DELETE', '/pages/revisions/{post_id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Revision:doDelete');
// end revision routes. --------------------

// /admin/cms/pages/actions/xx REST API (other actions for multiple items - use comma for multiple items).
$Rc->addRoute('PATCH', '/pages/actions/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Actions:doActions');
// /admin/cms/pages/actions/xx REST API (delete items - use comma for multiple items).
$Rc->addRoute('DELETE', '/pages/actions/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Pages\\Actions:doDelete');
// end pages management. ------------------------------------------------------------