<?php
/* @var $Rc \FastRoute\RouteCollector */
/* @var $this \Rdb\System\Router */


// translation matcher. ----------------------------------------------------------------------------------------------
// /admin/cms/translation-matcher page + REST API (display listing page, get multiple data via REST.)
$Rc->addRoute('GET', '/translation-matcher', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\TranslationMatcher\\Index:index');
// /admin/cms/translation-matcher/search-editing REST API.
$Rc->addRoute('GET', '/translation-matcher/search-editing', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\TranslationMatcher\\Index:doSearchEditing');
// /admin/cms/translation-matcher/xxx REST API (get a single data).
$Rc->addRoute('GET', '/translation-matcher/{tm_id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\TranslationMatcher\\Index:doGetData');

// /admin/cms/translation-matcher REST API (add item).
$Rc->addRoute('POST', '/translation-matcher', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\TranslationMatcher\\Add:doAdd');

// /admin/cms/translation-matcher/xxx REST API (edut item).
$Rc->addRoute('PATCH', '/translation-matcher/{tm_id:\d+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\TranslationMatcher\\Edit:doUpdate');

// /admin/cms/translation-matcher/xxx REST API (delete items - use comma for multiple items).
$Rc->addRoute('DELETE', '/translation-matcher/{tm_id:[0-9,]+}', '\\Rdb\\Modules\\RdbCMSA\\Controllers\\Admin\\TranslationMatcher\\Actions:doDelete');
// end translation matcher. -----------------------------------------------------------------------------------------