<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo $pageTitle; ?></h1>

                        <form id="rdbcmsaurlaliases-edit-form" class="rd-form horizontal rdba-edit-form" method="post">
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
                            <input id="alias_id" type="hidden" name="alias_id" value="<?php echo ($alias_id ?? ''); ?>">
                            <div class="form-result-placeholder"></div>

                            <div class="form-group">
                                <label class="control-label" for="alias_content_type"><?php echo d__('rdbcmsa', 'Content type'); ?></label>
                                <div class="control-wrapper">
                                    <input id="alias_content_type" type="text" name="alias_content_type" maxlength="191">
                                    <div class="form-description"><?php echo d__('rdbcmsa', 'Required if you want to use URL alias function.'); ?></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="alias_content_id"><?php echo d__('rdbcmsa', 'Content ID'); ?></label>
                                <div class="control-wrapper">
                                    <input id="alias_content_id" type="number" name="alias_content_id">
                                    <div class="form-description"><?php echo d__('rdbcmsa', 'Required if you want to use URL alias function.'); ?></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="alias_url"><?php echo d__('rdbcmsa', 'URL alias'); ?> <em>*</em></label>
                                <div class="control-wrapper">
                                    <div class="rd-input-group">
                                        <div class="rd-input-group-block prepend">
                                            <span class="rd-input-group-block-text"><?php echo $baseUrl . '/'; ?></span>
                                        </div>
                                        <input id="alias_url" class="rd-input-control" type="text" name="alias_url" maxlength="400" required="">
                                    </div>
                                    <div class="form-description"><?php echo d__('rdbcmsa', 'If you want to use URL redirect function, this is the URL the will be redirected from.'); ?></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="alias_redirect_to"><?php echo d__('rdbcmsa', 'Redirect to'); ?></label>
                                <div class="control-wrapper">
                                    <input id="alias_redirect_to" type="text" name="alias_redirect_to" maxlength="400">
                                    <div class="form-description">
                                        <?php printf(d__('rdbcmsa', 'Use the full URL or related URL start from %1$s. Do not begins with slash mark.'), '<samp>' . rtrim($appBasePath, '/') . '/' . '</samp>'); ?><br>
                                        <?php echo d__('rdbcmsa', 'Do not URL encode.'); ?><br>
                                        <?php echo d__('rdbcmsa', 'Required if you want to use URL redirect function.'); ?> 
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="alias_redirect_code"><?php echo d__('rdbcmsa', 'Redirect code'); ?></label>
                                <div class="control-wrapper">
                                    <input id="alias_redirect_code" type="number" name="alias_redirect_code" maxlength="3" min="300" max="399" placeholder="302">
                                    <div class="form-description">
                                        <?php printf(d__('rdbcmsa', '%1$sHTTP status code%2$s for redirection.'), '<a href="https://www.restapitutorial.com/httpstatuscodes.html" target="httpstatuscode">', '</a>'); ?><br>
                                        <?php echo d__('rdbcmsa', 'Required if you want to use URL redirect function.'); ?> 
                                    </div>
                                </div>
                            </div>

                            <div class="form-group submit-button-row">
                                <label class="control-label"></label>
                                <div class="control-wrapper">
                                    <button class="rd-button primary rdba-submit-button" type="submit"><?php echo __('Save'); ?></button>
                                </div>
                            </div>
                        </form>