<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo $pageTitle; ?> 
                            <a class="rd-button rdba-listpage-addnew" href="<?php echo $urls['addCategoryUrl']; ?>">
                                <i class="fa-solid fa-circle-plus"></i> <?php echo __('Add'); ?>
                            </a>
                        </h1>

                        <form id="rdbcmsa-contents-categories-form" class="rdba-datatables-form">
                            <div class="form-result-placeholder"></div>
                            <table id="contentsCategoriesTable" class="contentsCategoriesTable rdba-datatables-js responsive hover" width="100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.contentsCategoriesTable'), jQuery(this));"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo d__('rdbcmsa', 'Name'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'Description'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'URL'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'Status'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'Count'); ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.contentsCategoriesTable'), jQuery(this));"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo d__('rdbcmsa', 'Name'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'Description'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'URL'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'Status'); ?></th>
                                        <th><?php echo d__('rdbcmsa', 'Count'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </form>

                        <div id="rdbcmsa-contents-categories-dialog" class="rd-dialog-modal" data-click-outside-not-close="true">
                            <div class="rd-dialog rd-dialog-size-large" data-esc-key-not-close="true" aria-labelledby="rdbcmsa-contents-categories-dialog-label">
                                <div class="rd-dialog-header">
                                    <h4 id="rdbcmsa-contents-categories-dialog-label" class="rd-dialog-title"></h4>
                                    <button class="rd-dialog-close" type="button" aria-label="Close" data-dismiss="dialog">
                                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="rd-dialog-body">
                                </div>
                            </div>
                        </div><!--.rd-dialog-modal-->


                        <template id="rdba-datatables-row-actions">
                            <div class="row-actions">
                                <span class="action"><?php echo __('ID'); ?> {{tid}}</span>
                                <span class="action"><a class="rdba-listpage-edit" href="{{RdbCMSACategoriesIndexObject.editCategoryUrlBase}}/{{tid}}"><?php echo __('Edit'); ?></a></span> 
                                <span class="action"><?php echo d__('rdbcmsa', 'Position'); ?> {{t_position}}</span>
                                <span class="action">
                                    <a 
                                        class="rdba-taxonomy-view" 
                                        href="{{#replace '%tid%' tid}}{{#replace '%t_type%' t_type}}{{RdbCMSACategoriesIndexObject.viewCategoryFrontUrl}}{{/replace}}{{/replace}}"
                                    >
                                        <?php echo d__('rdbcmsa', 'View'); ?>
                                    </a>
                                </span>
                            </div>
                        </template>

                        <template id="rdba-datatables-result-controls">
                            <div class="col-xs-12 col-sm-6">
                                <label>
                                    <?php echo __('Search'); ?>
                                    <input id="rdba-filter-search" class="rdba-datatables-input-search" type="search" name="search" aria-control="contentsCategoriesTable">
                                </label>
                                <div class="rd-button-group">
                                    <button id="rdba-datatables-filter-button" class="rdba-datatables-filter-button rd-button" type="button"><?php echo __('Filter'); ?></button>
                                    <button class="rd-button dropdown-toggler" type="button" data-placement="bottom right">
                                        <i class="fa-solid fa-caret-down"></i>
                                        <span class="sr-only"><?php echo __('More'); ?></span>
                                    </button>
                                    <ul class="rd-dropdown">
                                        <li><a href="#reset" onclick="return RdbCMSACategoriesIndexController.resetDataTable();"><?php echo __('Reset'); ?></a></li>
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
                                    <select id="rdbcmsa-contents-categories-actions" class="rdbcmsa-contents-categories-actions rdba-actions-selectbox" name="categories-actions">
                                        <option value=""><?php echo __('Action'); ?></option>
                                        <option value="recount"><?php echo d__('rdbcmsa', 'Update total items'); ?></option>
                                        <option value="delete"><?php echo __('Delete'); ?></option>
                                    </select>
                                </label>
                                <button id="rdbcmsa-contents-categories-actions-button" class="rd-button" type="submit"><?php echo __('Apply'); ?></button>
                                <span class="action-status-placeholder"></span>
                            </div>
                        </template>