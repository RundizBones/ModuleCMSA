<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo ($pageTitle ?? d__('rdbcmsa', 'CMS admin settings')); ?> 
                        </h1>

                        <form id="rdba-settings-form" class="rd-form horizontal" method="post">
                            <div class="form-result-placeholder"></div>

                            <?php include '_settings_form_v.php'; ?> 

                            <div class="form-group submit-button-row">
                                <label class="control-label"></label>
                                <div class="control-wrapper submit-button-wrapper">
                                    <button class="rd-button primary rdba-submit-button" type="submit"><?php echo __('Save'); ?></button>
                                </div>
                            </div>
                        </form>