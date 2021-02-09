<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo $pageTitle; ?></h1>

                        <form id="rdbcmsa-edit-contents-category-form" class="rd-form horizontal rdba-edit-form">
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
                            <input id="tid" type="hidden" name="tid" value="<?php echo ($tid ?? 0); ?>">
                            <div class="form-result-placeholder"></div>

                            <div class="form-group">
                                <label class="control-label" for="parent_id"><?php echo d__('rdbcmsa', 'Parent category'); ?></label>
                                <div class="control-wrapper">
                                    <select id="parent_id" name="parent_id">
                                        <option value="0"><?php echo d__('rdbcmsa', 'None'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="t_name"><?php echo d__('rdbcmsa', 'Name'); ?> <em>*</em></label>
                                <div class="control-wrapper">
                                    <input id="t_name" type="text" name="t_name" maxlength="190" required="">
                                    <div class="form-description">
                                        <?php echo d__('rdbcmsa', 'HTML will be escaped.'); ?> 
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="t_description"><?php echo d__('rdbcmsa', 'Description'); ?></label>
                                <div class="control-wrapper">
                                    <textarea id="t_description" name="t_description" rows="4" maxlength="65535"></textarea>
                                    <div class="form-description">
                                        <?php echo d__('rdbcmsa', 'HTML is allowed.'); ?> 
                                    </div>
                                </div>
                            </div>
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
                                        <?php echo d__('rdbcmsa', 'The name will be use on address of this category. Do not enter special characters.'); ?> 
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="t_position"><?php echo d__('rdbcmsa', 'Position'); ?></label>
                                <div class="control-wrapper">
                                    <input id="t_position" type="number" name="t_position" min="1" max="900000000">
                                    <div class="form-description">
                                        <?php echo d__('rdbcmsa', 'Position of this category in the same parent.'); ?> 
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="t_status"><?php echo d__('rdbcmsa', 'Status'); ?></label>
                                <div class="control-wrapper">
                                    <select id="t_status" name="t_status">
                                        <option value="0"><?php echo d__('rdbcmsa', 'Unpublished'); ?></option>
                                        <option value="1" selected=""><?php echo d__('rdbcmsa', 'Published'); ?></option>
                                    </select>
                                    <div class="form-description">
                                        <?php echo d__('rdbcmsa', 'Any contents that is inside this category still accessible directly even if this category is unpublished.'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group submit-button-row">
                                <label class="control-label"></label>
                                <div class="control-wrapper">
                                    <button class="rd-button primary rdba-submit-button" type="submit"><?php echo __('Save'); ?></button>
                                    <span class="action-status-placeholder"></span>
                                </div>
                            </div>
                        </form>