<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo $pageTitle; ?> 
                        </h1>


                        <div class="rd-columns-flex-container">
                            <div class="column col-sm-6">
                                <form id="rdbcmsa-encodedecode-form" class="rd-form" method="post">
                                    <div class="form-group">
                                        <label class="control-label" for="original-string"><?php echo d__('rdbcmsa', 'Original string'); ?>:</label>
                                        <div class="control-wrapper">
                                            <textarea id="original-string" name="original-string" rows="5"></textarea>
                                            <div class="form-description"><?php echo d__('rdbcmsa', 'This form does not store any information that you have entered.') ?></div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label" for="direction"><?php echo d__('rdbcmsa', 'Encode/Decode'); ?>:</label>
                                        <div class="control-wrapper">
                                            <label><input type="radio" name="direction" value="encode" checked=""> <?php echo d__('rdbcmsa', 'Encode'); ?></label>
                                            <label><input type="radio" name="direction" value="decode"> <?php echo d__('rdbcmsa', 'Decode'); ?></label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label" for="use-function"><?php echo d__('rdbcmsa', 'Function to use'); ?>:</label>
                                        <div class="control-wrapper">
                                            <select id="use-function" name="use-function">
                                                <option value="base64" selected=""><?php echo d__('rdbcmsa', 'Base64'); ?></option>
                                                <option value="rawurlencode_rawurldecode"><?php echo  d__('rdbcmsa', 'Raw URL encode/Raw URL decode'); ?></option>
                                                <option value="htmlspecialchars"><?php echo  d__('rdbcmsa', 'HTML special chars'); ?></option>
                                                <option value="htmlentities"><?php echo  d__('rdbcmsa', 'HTML entities'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group submit-button-row">
                                        <div class="control-wrapper">
                                            <button class="rd-button primary" type="submit"><?php echo d__('rdbcmsa', 'Submit'); ?></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="column col-sm-6">
                                <h3><?php echo d__('rdbcmsa', 'Result'); ?></h3>
                                <div id="rdbcmsa-encodedecode-result"></div>
                            </div>
                        </div>