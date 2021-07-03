<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <div class="rd-columns-flex-container fix-columns-container-edge">
                            <div class="column col-md-3 rd-block-level-margin-bottom">
                                <h2 class="rdbcmsa-files-columns-header">
                                    <?php echo d__('rdbcmsa', 'Folders'); ?>&nbsp;
                                    <div class="rd-button-group">
                                        <button id="rdbcmsa-files-new-folder" class="rd-button" type="button" title="<?php echo esc_d__('rdbcmsa', 'New folder'); ?>"><i class="fas fa-folder-plus"></i></button>
                                        <button id="rdbcmsa-files-reload-folder" class="rd-button" type="button" title="<?php echo esc_d__('rdbcmsa', 'Reload'); ?>" onclick="RdbCMSAFilesIndexControllerFolders.reloadFolders();"><i class="reload-icon fas fa-sync-alt"></i></button>
                                    </div>
                                </h2>
                                <ul id="rdbcmsa-files-folders-list-container" class="rdbcmsa-files-folders-list-container">
                                    <li id="folder-root"><a class="rdbcmsa-files-folders-list-link" data-folderrelpath="" data-foldername=""><?php echo ($rootPublicFolderName ?? 'rdbadmin-public'); ?></a></li>
                                </ul>
                            </div><!--.column-->

                            <div class="column rd-block-level-margin-bottom">
                                <h2 class="rdbcmsa-files-columns-header">
                                    <?php echo d__('rdbcmsa', 'Files'); ?> 
                                    <div id="rdbcmsa-files-dropzone" class="rdbcmsa-files-dropzone" title="<?php echo esc_d__('rdbcmsa', 'Drop the files into this area to start upload.'); ?>">
                                        <span id="rdbcmsa-files-choose-files-button" class="rd-button info rd-inputfile rdbcmsa-button-upload-file" tabindex="0">
                                            <span class="label"><i class="fas fa-file-upload"></i> <?php echo d__('rdbcmsa', 'Choose files'); ?></span>
                                            <input id="files_inputfiles" type="file" name="files_inputfiles" tabindex="-1" multiple="multiple">
                                        </span>
                                        <span id="rdbcmsa-files-upload-status-placeholder"></span>
                                        <div id="form-description-in-files-dropzone" class="form-description">
                                            <?php echo d__('rdbcmsa', 'Click on choose files or drop files here to start upload.'); ?> 
                                            <?php printf(d__('rdbcmsa', 'Max file size %s.'), ini_get('upload_max_filesize')); ?> 
                                        </div>
                                    </div><!--.rdbcmsa-files-dropzone-->
                                    <a class="rd-button rdbcmsa-scan-unindex-files-button" href="<?php echo $urls['scanUnindexedUrl']; ?>" title="<?php echo esc_d__('rdbcmsa', 'Scan for unindexed files'); ?>">
                                        <span class="rdbcmsa-scan-icon-stack">
                                            <i class="far fa-list-alt"></i>
                                            <i class="fas fa-search rdbcmsa-scan-icon-search"></i>
                                        </span>
                                        <span class="hidden-over-xs"><?php echo d__('rdbcmsa', 'Scan for unindexed files'); ?></span>
                                    </a>
                                </h2>

                                <form id="rdbcmsa-files-list-form" class="rdba-datatables-form">
                                    <input id="rdbcmsa-files-filter-folder" type="hidden" name="filter-file_folder">
                                    <div class="form-result-placeholder"></div>
                                    <table id="filesListItemsTable" class="filesListItemsTable rdba-datatables-js responsive hover" width="100%">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.filesListItemsTable'), jQuery(this));"></th>
                                                <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                                <th class="column-primary" data-priority="1"><?php echo d__('rdbcmsa', 'File'); ?></th>
                                                <th><?php echo d__('rdbcmsa', 'Uploader'); ?></th>
                                                <th><?php echo d__('rdbcmsa', 'Size'); ?></th>
                                                <th><?php echo d__('rdbcmsa', 'Status'); ?></th>
                                                <th><?php echo d__('rdbcmsa', 'Date'); ?></th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <th></th>
                                                <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.filesListItemsTable'), jQuery(this));"></th>
                                                <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                                <th class="column-primary" data-priority="1"><?php echo d__('rdbcmsa', 'File'); ?></th>
                                                <th><?php echo d__('rdbcmsa', 'Uploader'); ?></th>
                                                <th><?php echo d__('rdbcmsa', 'Size'); ?></th>
                                                <th><?php echo d__('rdbcmsa', 'Status'); ?></th>
                                                <th><?php echo d__('rdbcmsa', 'Date'); ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </form>
                            </div><!--.column-->
                        </div><!--.rd-columns-flex-container-->



                        <div id="rdbcmsa-folder-name-dialog" class="rd-dialog-modal" data-click-outside-not-close="true">
                            <div class="rd-dialog" data-esc-key-not-close="true" aria-labelledby="rdbcmsa-folder-name-dialog-label">
                                <div class="rd-dialog-header">
                                    <h4 id="rdbcmsa-folder-name-dialog-label" class="rd-dialog-title"></h4>
                                    <button class="rd-dialog-close" type="button" aria-label="Close" data-dismiss="dialog">
                                        <i class="fas fa-times" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="rd-dialog-body">
                                    <div class="form-description form-fields-for-new-folder"><?php printf(d__('rdbcmsa', 'Create new folder in %s'), $rootPublicFolderName . '/<span id="rdbcmsa-folder-name-dialog-new-folder-in-display"></span>'); ?></div>
                                    <div class="form-description form-fields-for-rename-folder"><?php printf(d__('rdbcmsa', 'The folder that will be rename %s'), $rootPublicFolderName . '/<span id="rdbcmsa-folder-name-dialog-folder-willbe-rename"></span>'); ?></div>
                                    <input id="rdbcmsa-folder-name-dialog-is-createnew" type="hidden" name="is-createnew">
                                    <input id="rdbcmsa-folder-name-dialog-new-folder-in" class="form-fields-for-new-folder" type="hidden" name="new_folder_in">
                                    <input id="rdbcmsa-folder-name-dialog-folder-to-rename" class="form-fields-for-rename-folder" type="hidden" name="folder_to_rename">
                                    <input id="rdbcmsa-folder-name-dialog-new-folder-name" class="rdbcmsa-folder-name-dialog-folder-name" type="text" name="new_folder_name" maxlength="255" placeholder="<?php echo esc_d__('rdbcmsa', 'Folder name'); ?>">
                                </div>
                                <div class="rd-dialog-footer">
                                    <button id="rdbcmsa-folder-name-dialog-save-button" class="rd-button primary" type="button"><?php echo __('Save'); ?></button>
                                    <button class="rd-button" type="button" data-dismiss="dialog"><?php echo __('Close'); ?></button>
                                </div>
                            </div>
                        </div><!-- #rdbcmsa-folder-name-dialog -->



                        <div id="rdbcmsa-files-editing-dialog" class="rd-dialog-modal" data-click-outside-not-close="true">
                            <div class="rd-dialog rd-dialog-size-large" data-esc-key-not-close="true" aria-labelledby="rdbcmsa-files-editing-dialog-label">
                                <div class="rd-dialog-header">
                                    <h4 id="rdbcmsa-files-editing-dialog-label" class="rd-dialog-title"></h4>
                                    <button class="rd-dialog-close" type="button" aria-label="Close" data-dismiss="dialog">
                                        <i class="fas fa-times" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="rd-dialog-body">
                                </div>
                            </div>
                        </div><!--.rd-dialog-modal-->



                        <template id="rdba-datatables-row-actions">
                            <div class="row-actions">
                                <span class="action"><?php echo __('ID'); ?> {{file_id}}</span>
                                <span class="action"><a class="rdba-listpage-edit" href="{{RdbCMSAFilesCommonObject.editFileUrlBase}}/{{file_id}}"><?php echo __('Edit'); ?></a></span>
                                <span class="action"><a href="{{#replace '%file_id%' file_id}}{{RdbCMSAFilesCommonObject.downloadFileUrl}}{{/replace}}"><?php echo d__('rdbcmsa', 'Download'); ?></a></span>
                                <span class="action"><a href="{{#replace '%file_id%' file_id}}{{RdbCMSAFilesCommonObject.viewFileFrontUrl}}{{/replace}}" target="viewonfront"><?php echo d__('rdbcmsa', 'View'); ?></a></span>
                                {{#if file_metadata.video}}
                                <span class="action">{{file_metadata.video.width}}x{{file_metadata.video.height}}</span>
                                {{#if file_metadata.video.duration}}
                                <span class="action"><?php echo d__('rdbcmsa', 'Duration'); ?>: {{file_metadata.video.duration}}</span>
                                {{/if}}
                                {{else if file_metadata.audio}}
                                <span class="action"><?php echo d__('rdbcmsa', 'Duration'); ?>: {{file_metadata.audio.duration}}</span>
                                {{/if}}
                                {{#if file_metadata.image}}
                                <span class="action">{{file_metadata.image.width}}x{{file_metadata.image.height}}</span>
                                {{/if}}
                            </div>
                        </template>
                        <template id="rdba-datatables-result-controls">
                            <div class="col-xs-12 col-sm-6">
                                <label>
                                    <?php echo __('Search'); ?>
                                    <input id="rdba-filter-search" class="rdba-datatables-input-search" type="search" name="search" aria-control="filesListItemsTable">
                                </label>
                                <div class="rd-button-group">
                                    <button id="rdba-datatables-filter-button" class="rdba-datatables-filter-button rd-button" type="button"><?php echo __('Filter'); ?></button>
                                    <button class="rd-button dropdown-toggler" type="button" data-placement="bottom right">
                                        <i class="fas fa-caret-down"></i>
                                        <span class="sr-only"><?php echo __('More'); ?></span>
                                    </button>
                                    <ul class="rd-dropdown">
                                        <li><a href="#reset" onclick="return RdbCMSAFilesIndexControllerFiles.resetDataTable();"><?php echo __('Reset'); ?></a></li>
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
                                    <select id="rdbcmsa-files-list-actions" class="rdbcmsa-files-list-actions rdba-actions-selectbox" name="bulk-actions">
                                        <option value=""><?php echo __('Action'); ?></option>
                                        <option value="setwatermark"><?php echo d__('rdbcmsa', 'Apply watermark'); ?></option>
                                        <option value="removewatermark"><?php echo d__('rdbcmsa', 'Remove watermark'); ?></option>
                                        <option value="updatethumbnails"><?php echo d__('rdbcmsa', 'Update thumbnails'); ?></option>
                                        <option value="updatemeta"><?php echo d__('rdbcmsa', 'Update metadata'); ?></option>
                                        <option value="move"><?php echo d__('rdbcmsa', 'Move'); ?></option>
                                        <option value="delete"><?php echo __('Delete'); ?></option>
                                    </select>
                                </label>
                                <button id="rdbcmsa-files-list-actions-button" class="rd-button" type="submit"><?php echo __('Apply'); ?></button>
                                <span class="action-status-placeholder"></span>
                            </div>
                        </template>
                        <!-- the template below must use script tag because it contains {{> xxx}} command that will be error in template tag. -->
                        <script id="rdbcmsa-files-folders-listing" type="x-handlebars-template">
                                {{#each children}}
                                <li>
                                    <a class="rdbcmsa-files-folders-list-link" data-folderrelpath="{{relatePath}}" data-foldername="{{name}}">{{name}}</a>
                                    <div class="rd-button-group rdbcmsa-files-folder-commands">
                                        <button class="rd-button tiny rdbcmsa-files-rename-folder" data-folderrelpath="{{relatePath}}" data-foldername="{{name}}" title="<?php echo esc_d__('rdbcmsa', 'Rename'); ?>"><i class="fas fa-pen"></i></button>
                                        <button class="rd-button tiny rdbcmsa-files-delete-folder" data-folderrelpath="{{relatePath}}" data-foldername="{{name}}" title="<?php echo esc_d__('rdbcmsa', 'Delete'); ?>"><i class="fas fa-times"></i></button>
                                    </div>
                                    {{#if children}}
                                    <ul>
                                        {{> list}}
                                    </ul>
                                    {{/if}}
                                </li>
                                {{/each}}
                        </script>
                        <script id="rdbcmsa-files-folders-listing-main" type="x-handlebars-template">
                            <ul>
                            {{> list}}
                            </ul>
                        </script>