<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo $pageTitle; ?> 
                            <a class="rd-button rdba-listpage-addnew" href="<?php echo $urls['addPostUrl']; ?>">
                                <i class="fa-solid fa-circle-plus"></i> <?php echo __('Add'); ?>
                            </a>
                        </h1>

                        <form id="posts-list-form" class="rdba-datatables-form">
                            <div class="form-result-placeholder"></div>
                            <table id="postsListItemsTable" class="postsListItemsTable rdba-datatables-js responsive hover">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler('.postsListItemsTable', this);"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo d__('rdbcmsa', 'Title'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'URL'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'Author'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'Date'); ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler('.postsListItemsTable', this);"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo d__('rdbcmsa', 'Title'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'URL'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'Author'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'Date'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </form>


                        <template id="rdba-datatables-row-actions">
                            <div class="row-actions">
                                <span class="action"><?php echo __('ID'); ?> {{post_id}}</span>
                                <span class="action"><a class="rdba-listpage-edit" href="{{RdbCMSAPostsIndexObject.editPostUrlBase}}/{{post_id}}"><?php echo __('Edit'); ?></a></span>
                                <span class="action">
                                    <a 
                                        class="rdba-listpage-view" 
                                        href="{{#replace '%post_id%' post_id}}{{#replace '%post_type%' post_type}}{{RdbCMSAPostsIndexObject.viewPostFrontUrl}}{{/replace}}{{/replace}}">
                                        <?php echo d__('rdbcmsa', 'View'); ?> 
                                    </a>
                                </span>
                                <br>
                                {{#each languages}}
                                <span class="action">
                                    {{@key}}: 
                                    {{#ifnoteq @key ../RdbaUIXhrCommonData.currentLanguage}}
                                        {{#if id}}
                                        <a href="{{#replaceBaseUrl @key}}{{../RdbCMSAPostsIndexObject.editPostUrlBase}}{{/replaceBaseUrl}}/{{id}}" title="{{data.data_name}}"><i class="fa-solid fa-pen fontawesome-icon"></i></a>
                                        {{else}}
                                        <a href="{{#replaceBaseUrl @key}}{{../RdbCMSAPostsIndexObject.addPostUrl}}?translation-matcher-from-post_id={{../post_id}}{{/replaceBaseUrl}}" title="<?php echo esc_d__('rdbcmsa', 'Add a translation'); ?>" data-current-tid="{{../tid}}" data-language-id="{{@key}}"><i class="fa-solid fa-plus fontawesome-icon"></i></a>
                                        {{/if}}
                                    {{else}}
                                    <a class="rdba-listpage-edit" href="{{../RdbCMSAPostsIndexObject.editPostUrlBase}}/{{../post_id}}" title="<?php echo esc__('Edit'); ?>"><i class="fa-solid fa-pen fontawesome-icon"></i></a>
                                    {{/ifnoteq}}
                                </span>
                                {{/each}}
                            </div>
                        </template>

                        <template id="rdba-datatables-result-controls">
                            <div class="col-xs-12 col-sm-6">
                                <label>
                                    <?php echo d__('rdbcmsa', 'Status'); ?>
                                    <select id="rdba-filter-post_status" name="filter-post_status">
                                    </select>
                                </label>
                                <label>
                                    <?php echo d__('rdbcmsa', 'Author'); ?>
                                    <select id="rdba-filter-user_id" name="filter-user_id">
                                    </select>
                                </label>
                                <label>
                                    <?php echo __('Search'); ?>
                                    <input id="rdba-filter-search" class="rdba-datatables-input-search" type="search" name="search" aria-control="postsListItemsTable">
                                </label>
                                <div class="rd-button-group">
                                    <button id="rdba-datatables-filter-button" class="rdba-datatables-filter-button rd-button" type="button"><?php echo __('Filter'); ?></button>
                                    <button class="rd-button dropdown-toggler" type="button" data-placement="bottom right">
                                        <i class="fa-solid fa-caret-down"></i>
                                        <span class="sr-only"><?php echo __('More'); ?></span>
                                    </button>
                                    <ul class="rd-dropdown">
                                        <li><a href="#reset" onclick="return RdbCMSAPostsIndexController.resetDataTable();"><?php echo __('Reset'); ?></a></li>
                                    </ul>
                                </div>
                            </div>
                        </template>
                        <template id="rdba-datatables-result-controls-pagination">
                            <span class="rdba-datatables-result-controls-info">
                            {{#ifGE recordsFiltered 2}}
                                {{recordsFiltered}} <?php echo __('items'); ?>
                            {{else}}
                                {{recordsFiltered}} <?php echo __('item'); ?>
                            {{/ifGE}}
                            </span>
                        </template>
                        <template id="rdba-datatables-actions-controls">
                            <div class="col-xs-12 col-sm-6">
                                <label>
                                    <select id="posts-list-actions" class="posts-list-actions rdba-actions-selectbox" name="bulk-actions">
                                        <option value=""><?php echo __('Action'); ?></option>
                                        <option value="trash"><?php echo d__('rdbcmsa', 'Move to trash'); ?></option>
                                        <option value="restore"><?php echo d__('rdbcmsa', 'Restore'); ?></option>
                                        <option value="delete_permanently"><?php echo d__('rdbcmsa', 'Delete permanently'); ?></option>
                                    </select>
                                </label>
                                <button id="posts-list-actions-button" class="rd-button" type="submit"><?php echo __('Apply'); ?></button>
                                <span class="action-status-placeholder"></span>
                            </div>
                        </template>