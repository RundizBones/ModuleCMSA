<?php
/* @var $Rc \FastRoute\RouteCollector */
/* @var $this \Rdb\System\Router */


// files (media) management. ---------------------------------------------------------
// /admin/cms/files page + REST API (listing page - get data via REST).
$Rc->addRoute('GET', '/files', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Index:index');
// /admin/cms/files/xx REST API (get a single item data).
$Rc->addRoute('GET', '/files/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Index:doGetItem');
// /admin/cms/files/xx page (download a file).
$Rc->addRoute('GET', '/files/{id:\d+}/downloads', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Index:downloadItem');

// /admin/cms/files/browser page (file browser in dialog page).
$Rc->addRoute('GET', '/files/browser', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Browser:index');

// /admin/cms/files REST API (add an item).
$Rc->addRoute('POST', '/files', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Add:doAdd');

// /admin/cms/files/edit[/xx] page.
$Rc->addRoute('GET', '/files/edit[/{id:\d+}]', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Edit:index');
// /admin/cms/files/xx REST API (update an item).
$Rc->addRoute('PATCH', '/files/{id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Edit:doUpdate');

// /admin/cms/files/actions page (bulk actions confirmation).
$Rc->addRoute('GET', '/files/actions', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Actions:index');
// /admin/cms/files/xx REST API (update files data - use comma for multiple items).
$Rc->addRoute('PATCH', '/files/{id:[0-9,]+}/{action:[a-zA-Z0-9\+_\-]+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Actions:doUpdateData');
// /admin/cms/files/xx/move REST API (move files).
$Rc->addRoute('POST', '/files/{id:[0-9,]+}/move', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Move:doMove');
// /admin/cms/files/xx REST API (delete files - use comma for multiple items).
$Rc->addRoute('DELETE', '/files/{id:[0-9,]+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Actions:doDelete');

// /admin/cms/files/scan-unindexed page + REST API (scan unindexed files via REST).
$Rc->addRoute('GET', '/files/scan-unindexed', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\ScanUnindexed:index');
// /admin/cms/files/scan-unindexed REST API.
$Rc->addRoute('POST', '/files/scan-unindexed', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\ScanUnindexed:doIndex');

// folder tasks routes. ---------------------
// /admin/cms/files/folders REST API (list folders via REST).
$Rc->addRoute('GET', '/files/folders', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Folders:doGetFolders');
// /admin/cms/files/folders REST API (new folder via REST).
$Rc->addRoute('POST', '/files/folders', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Folders:doNewFolder');
// /admin/cms/files/folders REST API (rename folder via REST).
$Rc->addRoute('PATCH', '/files/folders', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Folders:doRenameFolder');
// /admin/cms/files/folders REST API (delete folder via REST).
$Rc->addRoute('DELETE', '/files/folders', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\Files\\Folders:doDeleteFolder');
// end folder tasks routes. ----------------
// end files (media) management. ----------------------------------------------------