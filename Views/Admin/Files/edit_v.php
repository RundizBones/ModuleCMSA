<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo $pageTitle; ?></h1>

                        <form id="files-edit-form" class="rd-form horizontal rdba-edit-form" method="post" action="">
                            <?php 
                            // use form html CSRF because this page can load via XHR, REST by HTML type and this can reduce double call to get CSRF values in JSON type again.
                            if (
                                isset($csrfName) && 
                                isset($csrfValue) && 
                                isset($csrfKeyPair[$csrfName]) &&
                                isset($csrfKeyPair[$csrfValue])
                            ) {
                            ?> 
                            <input id="rdba-form-csrf-name" type="hidden" name="<?php echo $csrfName; ?>" value="<?php echo $csrfKeyPair[$csrfName]; ?>">
                            <input id="rdba-form-csrf-value" type="hidden" name="<?php echo $csrfValue; ?>" value="<?php echo $csrfKeyPair[$csrfValue]; ?>">
                            <?php
                            }
                            ?> 
                            <input id="file_id" type="hidden" name="file_id" value="<?php echo ($file_id ?? ''); ?>">
                            <div class="form-result-placeholder"></div>

                            <div id="files-thumbnails-row" class="files-thumbnails-row rd-block-level-margin-bottom"></div>
                            <div id="files-actions-row" class="files-actions-row rd-block-level-margin-bottom"></div>
                            <div class="form-group">
                                <label class="control-label" for="file_media_name"><?php echo d__('rdbcmsa', 'Name'); ?></label>
                                <div class="control-wrapper">
                                    <input id="file_media_name" type="text" name="file_media_name" maxlength="190">
                                    <div class="form-description">
                                        <?php echo d__('rdbcmsa', 'This data will be use on public views.'); ?> 
                                        <?php echo d__('rdbcmsa', 'HTML will be escaped.'); ?> 
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="file_media_description"><?php echo d__('rdbcmsa', 'Description'); ?></label>
                                <div class="control-wrapper">
                                    <textarea id="file_media_description" name="file_media_description" rows="4" maxlength="65535"></textarea>
                                    <div class="form-description">
                                        <?php echo d__('rdbcmsa', 'This data will be use on public views.'); ?> 
                                        <?php echo d__('rdbcmsa', 'HTML is allowed.'); ?> 
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="file_media_keywords"><?php echo d__('rdbcmsa', 'Keywords'); ?></label>
                                <div class="control-wrapper">
                                    <input id="file_media_keywords" type="text" name="file_media_keywords" maxlength="190">
                                    <div class="form-description">
                                        <?php echo d__('rdbcmsa', 'HTML is not allowed.'); ?> 
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="file_status"><?php echo d__('rdbcmsa', 'Status'); ?></label>
                                <div class="control-wrapper">
                                    <label><input id="file_status" type="checkbox" name="file_status" value="1"> <?php echo d__('rdbcmsa', 'Published'); ?></label>
                                </div>
                            </div>
                            <div class="form-group submit-button-row">
                                <label class="control-label"></label>
                                <div class="control-wrapper">
                                    <button class="rd-button primary rdba-submit-button" type="submit"><?php echo __('Save'); ?></button>
                                </div>
                            </div>



                            <!-- move inside form because it will work in dialog page. -->
                            <template id="files-actions-row-template">
                                <span class="action"><a href="{{#replace '%file_id%' file_id}}{{RdbCMSAFilesCommonObject.downloadFileUrl}}{{/replace}}"><?php echo d__('rdbcmsa', 'Download'); ?></a></span>
                                {{#if file_metadata.video}}
                                <span class="action">{{RdbCMSAFilesCommonObject.txtVideoDimension}}: {{file_metadata.video.width}}x{{file_metadata.video.height}}</span>
                                {{/if}}
                                {{#if file_metadata.image}}
                                <span class="action">{{RdbCMSAFilesCommonObject.txtImageDimension}}: {{file_metadata.image.width}}x{{file_metadata.image.height}}</span>
                                {{/if}}
                            </template>
                        </form>