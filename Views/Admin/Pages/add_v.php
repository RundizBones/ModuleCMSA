<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo $pageTitle; ?></h1>

                        <form id="posts-add-form" class="rd-form horizontal rdba-edit-form" method="post">
                            <div class="form-result-placeholder"></div>

                            <div id="posts-editing-tabs" class="tabs rd-tabs">
                                <ul class="rd-tabs-nav">
                                    <li class="active"><a href="#post-tab-content"><?php echo esc_d__('rdbcmsa', 'Content'); ?></a></li>
                                    <li><a href="#post-tab-htmlhead"><?php echo esc_d__('rdbcmsa', 'HTML head'); ?></a></li>
                                    <li><a href="#post-tab-publishing"><?php echo esc_d__('rdbcmsa', 'Publishing'); ?></a></li>
                                    <li><a href="#post-tab-revision"><?php echo esc_d__('rdbcmsa', 'Revision'); ?></a></li>
                                    <?php if (isset($editPost) && $editPost === true) { ?>
                                    <li id="post-tab-nav-revisionhistory"><a href="#post-tab-revisionhistory"><?php echo esc_d__('rdbcmsa', 'Revision history'); ?></a></li>
                                    <?php }// endif; ?>
                                </ul>


                                <div id="post-tab-content" class="rd-tabs-content active">
                                    <div class="form-group">
                                        <label class="control-label" for="post_name"><?php echo d__('rdbcmsa', 'Title'); ?>  <em>*</em></label>
                                        <div class="control-wrapper">
                                            <!-- can't add `post_name` `required` attribute because it can be in an in-active tab. use js check and alert instead. -->
                                            <input id="post_name" type="text" name="post_name" maxlength="191" autofocus="">
                                            <div class="form-description">
                                                <?php echo d__('rdbcmsa', 'HTML will be escaped.'); ?> 
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label" for="revision_body_value"><?php echo d__('rdbcmsa', 'Content'); ?></label>
                                        <div class="control-wrapper">
                                            <textarea id="revision_body_value" name="revision_body_value" rows="12"></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label" for="post_feature_image"><?php echo d__('rdbcmsa', 'Featured image'); ?></label>
                                        <div class="control-wrapper">
                                            <input id="post_feature_image" type="hidden" name="post_feature_image">
                                            <div id="post_feature_image-wrapper">
                                                <div id="post_feature_image-preview"></div>
                                                <button id="post_feature_image-openbrowser" type="button" title="<?php echo esc_d__('rdbcmsa', 'Browse an image'); ?>" data-toggle="dialog" data-target="#dialog-image-browser">
                                                    <i class="fas fa-image"></i> 
                                                    <span class="screen-reader-only"><?php echo d__('rdbcmsa', 'Browse an image'); ?></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div><!--.rd-tabs-content-->


                                <div id="post-tab-htmlhead" class="rd-tabs-content">
                                    <div class="form-group">
                                        <label class="control-label" for="revision_head_value"><?php echo d__('rdbcmsa', 'Head elements'); ?></label>
                                        <div class="control-wrapper">
                                            <div id="revision_head_value-editor"></div><!--for ace editor-->
                                            <textarea id="revision_head_value" name="revision_head_value" rows="7" maxlength="1000"></textarea>
                                            <div class="form-description">
                                                <?php echo sprintf(d__('rdbcmsa', 'Optional elements such as meta tags, additional styles, or scripts that will be render within %1$s element.'), '<code>' . htmlspecialchars('<head>...</head>') . '</code>'); ?> 
                                            </div>
                                        </div>
                                    </div>
                                </div><!--.rd-tabs-content-->


                                <div id="post-tab-publishing" class="rd-tabs-content">
                                    <div class="rd-columns-flex-container fix-columns-container-edge">
                                        <div class="column col-xs-12">
                                            <div class="form-group">
                                                <label class="control-label" for="alias_url"><?php echo d__('rdbcmsa', 'URL alias'); ?></label>
                                                <div class="control-wrapper">
                                                    <div class="rd-input-group">
                                                        <div class="rd-input-group-block prepend">
                                                            <span class="rd-input-group-block-text"><?php echo $baseUrl . '/'; ?></span>
                                                        </div>
                                                        <input id="alias_url" class="rd-input-control" type="text" name="alias_url" maxlength="255">
                                                    </div>
                                                    <div class="form-description">
                                                        <?php echo d__('rdbcmsa', 'The name will be use on address of this page. Do not enter special characters.'); ?> 
                                                    </div>
                                                </div>
                                            </div>
                                        </div><!--.column-->

                                        <div class="column col-sm-6">
                                            <div class="form-group">
                                                <label class="control-label" for="prog_display_author"><?php echo d__('rdbcmsa', 'Author'); ?></label>
                                                <div class="control-wrapper">
                                                    <input id="user_id" type="hidden" name="user_id">
                                                    <input id="prog_display_author" type="text" readonly="">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="post_add"><?php echo d__('rdbcmsa', 'Created date'); ?></label>
                                                <div class="control-wrapper">
                                                    <input id="post_add" type="datetime-local" readonly="">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="post_update"><?php echo d__('rdbcmsa', 'Last update'); ?></label>
                                                <div class="control-wrapper">
                                                    <input id="post_update" type="datetime-local" readonly="">
                                                </div>
                                            </div>
                                        </div><!--.column-->
                                        <div class="column col-sm-6">
                                            <div class="form-group">
                                                <label class="control-label" for="post_status"><?php echo d__('rdbcmsa', 'Status'); ?></label>
                                                <div class="control-wrapper">
                                                    <select id="post_status" name="post_status">
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="post_publish_date"><?php echo d__('rdbcmsa', 'Published on'); ?></label>
                                                <div class="control-wrapper">
                                                    <input id="post_publish_date" type="datetime-local" name="post_publish_date">
                                                    <div class="form-description">
                                                        <?php echo sprintf(d__('rdbcmsa', 'Please enter in format %1$s. Example: %2$s.'), '<code>y-m-dTh:m</code>', '<time>' . date('Y-m-d\TH:i') . '</time>'); ?> 
                                                    </div>
                                                </div>
                                            </div>
                                        </div><!--.column-->
                                    </div><!--.rd-columns-flex-container-->
                                </div><!--.rd-tabs-content-->


                                <div id="post-tab-revision" class="rd-tabs-content">
                                    <div class="form-group">
                                        <label class="control-label"><?php echo d__('rdbcmsa', 'New revision'); ?></label>
                                        <div class="control-wrapper">
                                            <label>
                                                <input id="prog_enable_revision" type="checkbox" name="prog_enable_revision" value="1">
                                                <?php echo d__('rdbcmsa', 'Create new revision'); ?> 
                                            </label>
                                            <div class="form-description">
                                                <?php echo d__('rdbcmsa', 'If you choose to create new revision, it will not overwrite the previous content.'); ?> 
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label" for="revision_log"><?php echo d__('rdbcmsa', 'Revision log'); ?></label>
                                        <div class="control-wrapper">
                                            <textarea id="revision_log" name="revision_log" rows="5" maxlength="1000"></textarea>
                                        </div>
                                    </div>
                                </div><!--.rd-tabs-content-->


                                <?php if (isset($editPost) && $editPost === true) { ?>
                                <div id="post-tab-revisionhistory" class="rd-tabs-content">
                                    <?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Posts' . DIRECTORY_SEPARATOR . 'revisionHistory_v.php'; ?> 
                                </div><!--.rd-tabs-content-->
                                <?php }// endif; ?>
                            </div><!--.tabs-->



                            <div class="rdbcmsa-contents-posts-save-container">
                                <div class="form-group submit-button-row">
                                    <label class="control-label"></label>
                                    <div class="control-wrapper">
                                        <div class="rd-button-group">
                                            <button class="rd-button primary rdba-submit-button" type="submit" name="prog_save_command" value="save"><?php echo __('Save'); ?><span id="prog-current-post_status"></span></button>
                                            <?php if (isset($editPost) && $editPost === true) { ?> 
                                            <button class="rd-button primary dropdown-toggler" type="button"><i class="fas fa-caret-down"></i></button>
                                            <ul class="rd-dropdown">
                                                <li><button type="submit" name="prog_save_command" value="save_stay"><?php echo esc_d__('rdbcmsa', 'Save & stay'); ?></button></li>
                                            </ul>
                                            <?php }// endif; ?> 
                                        </div><!-- .rd-button-group -->
                                    </div>
                                </div>
                            </div>
                        </form>



                        <div id="dialog-image-browser" class="rd-dialog-modal">
                            <div class="rd-dialog rd-dialog-size-fullwindow">
                                <div class="rd-dialog-header">
                                    <h4 class="rd-dialog-title"><?php echo d__('rdbcmsa', 'Featured image'); ?></h4>
                                    <button class="rd-dialog-close" type="button" aria-label="<?php echo esc_d__('rdbcmsa', 'Close'); ?>" data-dismiss="dialog">
                                        <i class="fas fa-times" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="rd-dialog-body">
                                    <p>&hellip;</p>
                                </div>
                            </div>
                        </div>


                        <script id="rdbcmsa-featured-image-preview-elements" type="text/x-handlebars-template">
                            <p>
                                <a href="{{files.urls.original}}" target="featured_image_fullsize"><img class="rdbcmsa-files-featuredimage fluid" src="{{files.urls.thumbnail}}" alt="{{files.file_media_name}}"></a>
                                <a id="post_feature_image-removebutton"><?php echo d__('rdbcmsa', 'Remove featured image'); ?></a>
                            </p>
                        </script>