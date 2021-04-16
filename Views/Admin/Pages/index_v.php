<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo $pageTitle; ?> 
                            <a class="rd-button rdba-listpage-addnew" href="<?php echo $urls['addPostUrl']; ?>">
                                <i class="fas fa-plus-circle"></i> <?php echo __('Add'); ?>
                            </a>
                        </h1>

                        <form id="posts-list-form" class="rdba-datatables-form">
                            <div class="form-result-placeholder"></div>
                            <table id="postsListItemsTable" class="postsListItemsTable rdba-datatables-js responsive hover" width="100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.postsListItemsTable'), jQuery(this));"></th>
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
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.postsListItemsTable'), jQuery(this));"></th>
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
                                        <i class="fas fa-caret-down"></i>
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