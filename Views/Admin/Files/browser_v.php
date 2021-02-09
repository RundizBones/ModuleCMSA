<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <form id="rdbcmsa-files-list-form" class="rd-columns-flex-container rd-form">
                            <div class="column rdbcmsa-files-browser-dialog-column-main">
                                <!--upload row-->
                                <div id="rdbcmsa-files-dropzone" class="rdbcmsa-files-dropzone rd-content-level-margin-bottom" title="<?php echo esc_d__('rdbcmsa', 'Drop the files into this area to start upload.'); ?>">
                                    <span id="rdbcmsa-files-choose-files-button" class="rd-button info rd-inputfile" tabindex="0">
                                        <span class="label"><i class="fas fa-file-upload"></i> <?php echo d__('rdbcmsa', 'Choose files'); ?></span>
                                        <input id="files_inputfiles" type="file" name="files_inputfiles" tabindex="-1" multiple="multiple">
                                    </span>
                                    <span id="rdbcmsa-files-upload-status-placeholder"></span>
                                    <div id="form-description-in-files-dropzone" class="form-description">
                                        <?php echo d__('rdbcmsa', 'Click on choose files or drop files here to start upload.'); ?> 
                                        <?php printf(d__('rdbcmsa', 'Max file size %s.'), ini_get('upload_max_filesize')); ?> 
                                    </div>
                                </div><!--.rdbcmsa-files-dropzone-->
                                <!--filters row-->
                                <div class="rdbcmsa-filters-row rd-content-level-margin-bottom">
                                    <label>
                                        <?php echo d__('rdbcmsa', 'Folder'); ?> 
                                        <select id="rdbcmsa-files-filter-folder" name="filter-file_folder">
                                            <option value=""><?php echo $rootPublicFolderName; ?></option>
                                        </select>
                                    </label>
                                    <?php
                                    if (
                                        (isset($featuredImage) && $featuredImage === true) ||
                                        (isset($selectImages) && $selectImages === true)
                                    ) {
                                    ?> 
                                    <input id="rdba-filter-mimetype" type="hidden" name="filter-mimetype" value="image/">
                                    <?php
                                    } else {
                                    ?> 
                                    <label>
                                        <?php echo d__('rdbcmsa', 'File type'); ?> 
                                        <select id="rdba-filter-mimetype" name="filter-mimetype">
                                            <option value=""><?php echo d__('rdbcmsa', 'All'); ?></option>
                                            <option value="image/"><?php echo d__('rdbcmsa', 'Images'); ?></option>
                                            <option value="audio/"><?php echo d__('rdbcmsa', 'Audio'); ?></option>
                                            <option value="video/"><?php echo d__('rdbcmsa', 'Video'); ?></option>
                                        </select>
                                    </label>
                                    <?php
                                    }// endif; $featuredImage
                                    ?> 
                                    <label>
                                        <?php echo __('Search'); ?> 
                                        <input id="rdba-filter-search" type="search" name="search">
                                    </label>
                                    <input id="rdbcmsa-files-filter-filestatus" type="hidden" name="filter-file_status" value="1">
                                    <button id="rdba-datatables-filter-button" class="rd-button" type="button"><?php echo __('Filter'); ?></button>
                                </div><!--.rdbcmsa-filters-row-->
                                <!--files list placeholder row-->
                                <input id="rdbcmsa-files-current-page" type="hidden" name="files-current-page" value="1">
                                <div id="rdbcmsa-files-list-placeholder" class="rdbcmsa-files-list-placeholder"></div>
                            </div><!--.column-->

                            <div class="column col-md-3 rdbcmsa-files-browser-dialog-column-aside">
                                <input id="full-url-to-rootpublic" type="hidden" value="<?php echo $fullUrlToRootPublicStorage; ?>">
                                <?php
                                if (
                                    isset($featuredImage) && $featuredImage === true
                                ) {
                                ?> 
                                <div class="form-group submit-button-row">
                                    <label class="control-label"></label>
                                    <div class="control-wrapper">
                                        <button id="rdbcmsa-files-setfeaturedimage-button" class="rd-button primary rdba-submit-button rdbcmsa-files-setfeaturedimage-button" type="button">
                                            <?php 
                                            if (isset($setButtonMessage) && is_string($setButtonMessage)) {
                                                echo $setButtonMessage;
                                            } else {
                                                echo d__('rdbcmsa', 'Set featured image'); 
                                            }
                                            ?> 
                                        </button>
                                    </div>
                                </div>
                                <?php
                                } elseif (
                                    isset($selectImages) && $selectImages === true
                                ) {
                                ?> 
                                <div class="form-group submit-button-row">
                                    <label class="control-label"></label>
                                    <div class="control-wrapper">
                                        <button id="rdbcmsa-files-selectimages-button" class="rd-button primary rdba-submit-button rdbcmsa-files-selectimages-button" type="button">
                                            <?php 
                                            if (isset($setButtonMessage) && is_string($setButtonMessage)) {
                                                echo $setButtonMessage;
                                            } else {
                                                echo d__('rdbcmsa', 'Set select images'); 
                                            }
                                            ?> 
                                        </button>
                                    </div>
                                </div>
                                <?php
                                } else {
                                ?> 
                                <div class="form-group">
                                    <label class="control-label" for="files-link-to"><?php echo d__('rdbcmsa', 'Link to'); ?></label>
                                    <div class="control-wrapper">
                                        <select id="files-link-to" name="files-link-to">
                                            <option value=""><?php echo d__('rdbcmsa', 'None'); ?></option>
                                            <option value="file"><?php echo d__('rdbcmsa', 'Actual file'); ?></option>
                                            <option value="attachment"><?php echo d__('rdbcmsa', 'Attachment page'); ?></option>
                                            <option value="embed"><?php echo d__('rdbcmsa', 'Embed media player (for video or audio, otherwise it will use attachment page).'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label" for="files-image-size"><?php echo d__('rdbcmsa', 'Image size'); ?></label>
                                    <div class="control-wrapper">
                                        <select id="files-image-size" name="files-image-size">
                                            <?php
                                            if (isset($thumbnailSizes) && is_array($thumbnailSizes)) {
                                                foreach ($thumbnailSizes as $name => list($width, $height)) {
                                                    echo '<option value="' . htmlspecialchars($name, ENT_QUOTES) . '">' . $width . 'x' . $height . '</option>' . PHP_EOL;
                                                }// endforeach;
                                                unset($name, $width, $height);
                                            }
                                            ?> 
                                            <option value="original"><?php echo d__('rdbcmsa', 'Original'); ?></option>
                                        </select>
                                        <div class="form-description">
                                            <?php echo d__('rdbcmsa', 'If the selected resolution is not available, it will automatically use a smaller resolution or the original file.'); ?> 
                                            <?php echo d__('rdbcmsa', 'This option will be use for image only.'); ?> 
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group submit-button-row">
                                    <label class="control-label"></label>
                                    <div class="control-wrapper">
                                        <button id="rdbcmsa-files-insert-button" class="rd-button primary rdba-submit-button rdbcmsa-files-insert-button" type="button">
                                            <?php 
                                            if (isset($setButtonMessage) && is_string($setButtonMessage)) {
                                                echo $setButtonMessage;
                                            } else {
                                                echo d__('rdbcmsa', 'Insert'); 
                                            }
                                            ?> 
                                        </button>
                                    </div>
                                </div>
                                <?php
                                }// endif; $featuredImage
                                ?> 
                            </div><!--.column-->
                        </form><!--.rd-columns-flex-container-->



                            <!-- the template below use script tag to prevent stupid Firefox from retrieve image template url `<img src={{xxx}}>` where {{xxx}} will be 404 not found. -->
                            <script id="rdbcmsa-files-list-file-item-template" type="x-handlebars-template">
                                {{#each listItems}}
                                <div class="rdbcmsa-files-file-wrapper">
                                    <div class="rdbcmsa-files-file-preview">
                                        {{#isImage file_ext}}
                                        <div class="rdbcmsa-files-file-thumbnail">
                                            <a href="{{#getPublicUrlWithFolderPrefix this}}{{/getPublicUrlWithFolderPrefix}}/{{file_name}}" target="realImageFile">
                                                {{#if thumbnails.thumb300}}
                                                <img class="fluid" src="{{thumbnails.thumb300}}" alt="">
                                                {{else}}
                                                <img class="fluid" src="{{#getPublicUrlWithFolderPrefix this}}{{/getPublicUrlWithFolderPrefix}}/{{file_name}}" alt="">
                                                {{/if}}
                                            </a>
                                        </div>
                                        {{else}}
                                        <a href="{{#getPublicUrlWithFolderPrefix this}}{{/getPublicUrlWithFolderPrefix}}/{{file_name}}" target="realFile">
                                            <i class="far fa-file rdbcmsa-files-generic-file-icon"></i>
                                        </a>
                                        {{/isImage}}
                                        <label class="rdbcmsa-files-file-name">
                                            <input class="rdbcmsa-files-input-checkbox-fileid" type="checkbox" name="file_id[{{file_id}}]" value="{{file_id}}" 
                                                data-file_folder="{{file_folder}}" 
                                                data-file_name="{{file_name}}" 
                                                data-file_original_name="{{file_original_name}}"
                                                data-file_mime_type="{{file_mime_type}}"
                                                data-file_ext="{{file_ext}}"
                                                data-file_media_name="{{file_media_name}}"
                                                <?php
                                                if (isset($thumbnailSizes) && is_array($thumbnailSizes)) {
                                                    foreach ($thumbnailSizes as $name => list($width, $height)) {
                                                ?> 
                                                {{#if thumbnails.<?php echo $name; ?>}}
                                                data-thumbnails-<?php echo $name; ?>="{{thumbnails.<?php echo $name; ?>}}"
                                                {{/if}}
                                                <?php
                                                    }// endforeach;
                                                    unset($height, $name, $width);
                                                }// endif; $thumbnailSizes
                                                unset($thumbnailSizes);
                                                ?> 
                                                {{#if file_metadata.video}}
                                                data-video-width="{{file_metadata.video.width}}"
                                                data-video-height="{{file_metadata.video.height}}"
                                                {{/if}}
                                                {{#if file_metadata.image}}
                                                data-image-width="{{file_metadata.image.width}}"
                                                data-image-height="{{file_metadata.image.height}}"
                                                {{/if}}
                                            > 
                                            {{file_original_name}}
                                        </label>
                                        <div class="rdbcmsa-files-file-tools">
                                            <small><a href="{{../RdbCMSAFilesCommonObject.editFileUrlBase}}/{{file_id}}" target="editFile"><?php echo __('Edit'); ?></a></small>
                                            <small><a href="{{#replace '%file_id%' file_id}}{{../RdbCMSAFilesCommonObject.downloadFileUrl}}{{/replace}}" target="downloadFile"><?php echo d__('rdbcmsa', 'Download'); ?></a></small>
                                        </div>
                                    </div>
                                </div>
                                {{/each}}
                            </script>
                            <!-- the template below must use script tag because it contains {{> xxx}} command that will be error in template tag. -->
                            <script id="rdbcmsa-files-folders-listing" type="x-handlebars-template">
                                {{#each children}}
                                <option value="{{relatePath}}" data-folderrelpath="{{relatePath}}" data-foldername="{{name}}">
                                    {{#repeat depth '&mdash; '}}{{/repeat}}{{name}}
                                </option>
                                    {{#if children}}
                                        {{> list}}
                                    {{/if}}
                                {{/each}}
                            </script>
                            <script id="rdbcmsa-files-folders-listing-main" type="x-handlebars-template">
                                {{> list}}
                            </script>