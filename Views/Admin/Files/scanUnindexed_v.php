<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo $pageTitle; ?></h1>

                        <div id="rdbcmsa-scan-unindexed-files-container">
                            <form id="rdbcmsa-scan-unindexed-files-form" class="rd-form rd-block-level-margin-bottom" method="get">
                                <div class="form-result-placeholder"></div>

                                <p class="form-description"><?php echo sprintf(d__('rdbcmsa', 'This can be scan for maximum %$1d files at a time.'), $scanMaxFilesATime); ?></p>
                                <button class="rd-button info" type="submit" name="action" value="start-scan"><i class="fas fa-search"></i> <?php echo d__('rdbcmsa', 'Start scan'); ?></button>
                                <span class="rdbcmsa-scan-status-icon-placeholder"></span>
                            </form>

                            <form id="rdbcmsa-scan-unindexed-files-action-form" class="rd-form rd-hidden" method="post">
                                <h3><?php echo d__('rdbcmsa', 'Scanned result'); ?></h3>
                                <div class="form-action-result-placeholder"></div>

                                <ul id="rdbcmsa-unindexed-files-listing"></ul>

                                <div class="form-group">
                                    <div class="control-wrapper">
                                        <button class="rd-button primary" type="submit" name="action" value="index-selected-files" disabled=""><?php echo d__('rdbcmsa', 'Index selected items'); ?></button>
                                        <span class="rdbcmsa-unindexed-files-action-status-icon-placeholder"></span>
                                    </div>
                                </div>
                            </form>
                        </div><!--#rdbcmsa-scan-unindexed-files-container-->


                        <template id="rdbcmsa-files-list-template">
                            {{#each items}}
                            <li>
                                <label>
                                    <input 
                                        type="checkbox" 
                                        name="realPathHash[]" 
                                        value="{{realPathHash}}"
                                        data-file_folder="{{file_folder}}"
                                        data-file_name="{{file_name}}"
                                        data-real-path="{{realPath}}"
                                    > 
                                    {{realPath}}
                                    <a href="{{url}}" target="viewFile"><?php echo d__('rdbcmsa', 'View this file.'); ?></a>
                                </label>
                            </li>
                            {{/each}}
                        </template>