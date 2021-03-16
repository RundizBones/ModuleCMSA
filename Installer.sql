/**
 * Installer SQL.
 * 
 * Please follow these instruction strictly.
 * The table name in this file must wrap with %$...% and have no prefix. Example: `%$users%` will be converted to `prefix_users`.
 * No ENGINE=xxx in the SQL.
 * No COLLATE xxx in each table or column (except it is special such as `utf8_bin` for work with case sensitive).
 * Use only CHARSET=utf8 in the CREATE TABLE, nothing else, no utf8mb4 or anything. Just utf8.
 *
 * DO NOT just paste the SQL data that exported from MySQL. Please modify by read the instruction above first.
 */


-- Begins the SQL string below this line. ------------------------------------------------------------------


SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


-- taxonomy (category, tag) and related tables. ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `%$taxonomy_term_data%` (
  `tid` bigint(20) NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) NOT NULL DEFAULT '0' COMMENT 'refer to tid. this column value must be integer. if it is root then this value must be 0, it can not be NULL.',
  `language` varchar(5) DEFAULT NULL COMMENT 'language that matched the framework language locale URL.',
  `t_type` varchar(255) DEFAULT NULL COMMENT 'type of taxonomy. english only. example: category, tag.',
  `t_total` int(11) NOT NULL DEFAULT 0 COMMENT 'total posts in this taxonomy.',
  `t_name` varchar(191) DEFAULT NULL COMMENT 'taxonomy name or category name, tag name.',
  `t_description` longtext DEFAULT NULL COMMENT 'the description for this taxonomy.',
  `t_status` int(1) NOT NULL DEFAULT 1 COMMENT '0=not publish, 1=published',
  `t_position` int(9) NOT NULL DEFAULT '0' COMMENT 'position when sort/order items.',
  `t_level` int(10) NOT NULL DEFAULT '1' COMMENT 'deep level of taxonomy hierarchy. begins at 1 (no sub items).',
  `t_left` int(10) NOT NULL DEFAULT '0' COMMENT 'for nested set model calculation. this will be able to select filtered parent id and all of its children.',
  `t_right` int(10) NOT NULL DEFAULT '0' COMMENT 'for nested set model calculation. this will be able to select filtered parent id and all of its children.',
  `t_head_value` mediumtext DEFAULT NULL COMMENT 'contents will be render inside <head>...</head> tag. this can be script and style or anything valid in the HTML head tag.',
  PRIMARY KEY (`tid`),
  KEY `parent_id` (`parent_id`)
) DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='contain taxonomy such as category, tag.' AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `%$taxonomy_fields%` (
  `taxonomyfield_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tid` bigint(20) NOT NULL COMMENT 'refer to taxonomy_term_data.tid',
  `field_name` varchar(191) DEFAULT NULL COMMENT 'field name.',
  `field_value` longtext DEFAULT NULL COMMENT 'field value.',
  `field_description` varchar(100) DEFAULT NULL COMMENT 'for describe what is this field for.',
  PRIMARY KEY (`taxonomyfield_id`),
  KEY `tid` (`tid`)
) DEFAULT CHARSET=utf8 COMMENT='contain taxonomy fields or additional data from taxonomy table.' AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `%$taxonomy_index%` (
  `index_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL DEFAULT 0 COMMENT 'refer to posts.post_id',
  `tid` bigint(20) NOT NULL DEFAULT 0 COMMENT 'refer to taxonomy_term_data.tid',
  `ti_position` int(9) NOT NULL DEFAULT 1 COMMENT 'position of the post in this tid. new position has larger number.',
  `ti_create` datetime DEFAULT NULL COMMENT 'local created date time.',
  `ti_create_gmt` datetime DEFAULT NULL COMMENT 'gmt created date time.',
  PRIMARY KEY (`index_id`),
  KEY `post_id` (`post_id`),
  KEY `tid` (`tid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='contain id between taxonomy/posts.' AUTO_INCREMENT=1 ;


-- post and related tables. ------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `%$posts%` (
  `post_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) NOT NULL DEFAULT '0' COMMENT 'refer to post_id. this column value must be integer. if it is root then this value must be 0, it can not be NULL.',
  `revision_id` bigint(20) DEFAULT NULL COMMENT 'refer to post_revision.revision_id. the revision id here will be use as current content.',
  `user_id` bigint(20) NOT NULL COMMENT 'refer to users.user_id who created this.',
  `post_type` varchar(255) DEFAULT NULL COMMENT 'post type. english only. example: article, page.',
  `language` varchar(5) DEFAULT NULL COMMENT 'language that matched the framework language locale URL.',
  `post_name` varchar(191) DEFAULT NULL COMMENT 'post name or post title.',
  `post_feature_image` bigint(20) DEFAULT NULL COMMENT 'refer to files.file_id',
  `post_comment` int(1) NOT NULL DEFAULT 0 COMMENT '0=disallowed comment, 1=allowed comment',
  `post_status` int(1) NOT NULL DEFAULT 1 COMMENT '0=draft, 1=published, 2=schedule, 3=pending, 4=private, 5=trash, 6=inherit',
  `post_add` datetime DEFAULT NULL COMMENT 'date/time that this post was added or created.',
  `post_add_gmt` datetime DEFAULT NULL COMMENT 'add or create date/time in GMT.',
  `post_update` datetime DEFAULT NULL COMMENT 'date/time that this post was last updated.',
  `post_update_gmt` datetime DEFAULT NULL COMMENT 'last update in GMT.',
  `post_publish_date` datetime DEFAULT NULL COMMENT 'date/time that this post was published (or will be published as schedule).',
  `post_publish_date_gmt` datetime DEFAULT NULL COMMENT 'publish date/time in GMT.',
  `post_content_settings` text DEFAULT NULL COMMENT 'store serialize array of settings',
  PRIMARY KEY (`post_id`),
  KEY `parent_id` (`parent_id`),
  KEY `revision_id` (`revision_id`),
  KEY `user_id` (`user_id`),
  KEY `post_feature_image` (`post_feature_image`)
) DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='contain posts data such as content, article, page.' AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `%$post_revision%` (
  `revision_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL COMMENT 'refer to posts.post_id',
  `user_id` bigint(20) NOT NULL COMMENT 'refer to users.user_id who created this.',
  `revision_head_value` mediumtext DEFAULT NULL COMMENT 'contents will be render inside <head>...</head> tag. this can be script and style or anything valid in the HTML head tag.',
  `revision_body_value` longtext DEFAULT NULL COMMENT 'the contents.',
  `revision_body_summary` text DEFAULT NULL COMMENT 'the content summary.',
  `revision_log` text DEFAULT NULL COMMENT 'explain that what was changed.',
  `revision_status` int(1) NOT NULL DEFAULT 0 COMMENT '0=normal revision, 1=auto save',
  `revision_date` datetime DEFAULT NULL COMMENT 'revision created date/time.',
  `revision_date_gmt` datetime DEFAULT NULL COMMENT 'revision created date/time in GMT.',
  PRIMARY KEY (`revision_id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`)
) DEFAULT CHARSET=utf8 COMMENT='contain post revision to keep current content and the content in history if editor want to keep it.' AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `%$post_fields%` (
  `postfield_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL COMMENT 'refer to posts.post_id',
  `field_name` varchar(191) DEFAULT NULL COMMENT 'field name.',
  `field_value` longtext DEFAULT NULL COMMENT 'field value.',
  `field_description` varchar(100) DEFAULT NULL COMMENT 'for describe what is this field for.',
  PRIMARY KEY (`postfield_id`),
  KEY `post_id` (`post_id`)
) DEFAULT CHARSET=utf8 COMMENT='contain post fields or additional data from post table.' AUTO_INCREMENT=1 ;


-- files (media, picture) table. --------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `%$files%` (
  `file_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT 'refer to users.user_id who created this.',
  `file_folder` text DEFAULT NULL COMMENT 'folder that store this file. related from different locations depend on visibility. not url encoded. not begins or end with slash. not set to null, use empty string instead. example books/cartoon.',
  `file_visibility` int(1) NOT NULL DEFAULT 0 COMMENT '0=non-public and the file must be in framework storage folder, 1=public and the file must be in [public_path]/rdbadmin-public folder, 2=custom path that related from framework root.',
  `file_custom_path` text DEFAULT NULL COMMENT 'custom file path to the file (renamed) related from framework root (ROOT_PATH constant) but not begins with slash. leave null if not use.',
  `file_name` varchar(255) DEFAULT NULL COMMENT 'the real file name with extension (in case that it was renamed then store renamed in this field). should be english, not include any path or slash.',
  `file_original_name` varchar(191) DEFAULT NULL COMMENT 'the original file name before it was renamed. just file name with extension, no path or slash.',
  `file_mime_type` varchar(255) DEFAULT NULL COMMENT 'file mime type.',
  `file_ext` varchar(50) DEFAULT NULL COMMENT 'file extension without dot.',
  `file_size` int(11) NOT NULL DEFAULT 0 COMMENT 'file size in byte.',
  `file_metadata` longtext DEFAULT NULL COMMENT 'file metadata such as video, image width, height, etc. values are in JSON data.',
  `file_media_name` varchar(191) DEFAULT NULL COMMENT 'the title of this file that may use in front-end.',
  `file_media_description` text DEFAULT NULL COMMENT 'the description of this file.',
  `file_media_keywords` varchar(191) DEFAULT NULL COMMENT 'the keywords of this file for use in searching.',
  `file_status` int(1) NOT NULL DEFAULT 1 COMMENT '0=disabled cannot access (it can still be accessible if it is in public folder), 1=enabled.',
  `file_add` datetime DEFAULT NULL COMMENT 'uploaded date/time.',
  `file_add_gmt` datetime DEFAULT NULL COMMENT 'uploaded date/time in GMT.',
  `file_update` datetime DEFAULT NULL COMMENT 'last update.',
  `file_update_gmt` datetime DEFAULT NULL COMMENT 'last update in GMT.',
  PRIMARY KEY (`file_id`),
  KEY `user_id` (`user_id`)
) DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='contain uploaded files.' AUTO_INCREMENT=1 ;


-- URL aliases table. -------------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `%$url_aliases%` (
  `alias_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `alias_content_type` varchar(255) DEFAULT NULL COMMENT 'content type. english only. example: article, page, category, tag, ...etc...',
  `alias_content_id` bigint(20) DEFAULT NULL COMMENT 'the content id. related to tables such as taxonomy_term_data.tid, posts.post_id',
  `language` varchar(5) DEFAULT NULL,
  `alias_url` tinytext DEFAULT NULL COMMENT 'the URL alias of this content. related from installed (root) URL. not begins with slash. contain no language URL.',
  `alias_url_encoded` text DEFAULT NULL COMMENT 'the URL encoded of URL alias.',
  `alias_redirect_to` tinytext DEFAULT NULL COMMENT 'only use if there is redirect. if full URL it must contain ://. otherwise, related from installed (root) URL. not begins with slash. contain no language URL.',
  `alias_redirect_to_encoded` text DEFAULT NULL COMMENT 'the URL encoded to redirect to.',
  `alias_redirect_code` int(5) DEFAULT NULL COMMENT '3xx http status code.',
  PRIMARY KEY (`alias_id`),
  KEY `alias_content_id` (`alias_content_id`)
) DEFAULT CHARSET=utf8 COMMENT='contain URL aliases to look up to its source or contain redirections.' AUTO_INCREMENT=1 ;


-- translation matcher table. ------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `%$translation_matcher%` (
  `tm_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tm_table` varchar(32) DEFAULT NULL COMMENT 'translation matcher for table. value can be just posts, taxonomy_term_data',
  `matches` JSON DEFAULT NULL COMMENT 'the object data where key is language locale URL and its value is the table type ID. Example: `{"th": 12, "en-US": 26}`' CHECK (JSON_VALID(matches)),
  PRIMARY KEY (`tm_id`)
) DEFAULT CHARSET=utf8 COMMENT='contain posts and taxonomy translation matched in any post types or taxonomy types.' AUTO_INCREMENT=1 ;