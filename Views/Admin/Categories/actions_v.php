<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo $pageTitle; ?></h1>

                        <form id="rdbcmsa-actions-contents-categories-form" class="rd-form horizontal rdba-edit-form">
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
                            <div class="form-result-placeholder"><?php
                            if (function_exists('renderAlertHtml') && isset($formResultMessage)) {
                                echo renderAlertHtml($formResultMessage, ($formResultStatus ?? ''), false);
                            }
                            ?></div>

                            <input id="bulk-action" type="hidden" name="action" value="<?php if (isset($action)) {echo htmlspecialchars($action, ENT_QUOTES);} ?>">
                            <input id="bulk-tids" type="hidden" name="tids" value="<?php if (isset($tids)) {echo htmlspecialchars($tids, ENT_QUOTES);} ?>">

                            <h3><?php if (isset($actionText)) {echo $actionText;} ?></h3>
                            <?php if (isset($listSelectedCategories['items']) && is_array($listSelectedCategories['items']) && !empty($listSelectedCategories['items'])) { ?>
                            <ul>
                                <?php foreach ($listSelectedCategories['items'] as $row) { ?> 
                                <li>
                                    <?php
                                    if ($row->t_level > 1) {
                                        echo str_repeat('&mdash; ', ($row->t_level - 1));
                                    }
                                    if (isset($tid_array) && is_array($tid_array) && in_array($row->tid, $tid_array)) {
                                        echo '<strong>';
                                        $selectedItem = true;
                                    }
                                    ?> 
                                    <?php echo $row->t_name; ?> <a href="<?php echo $urls['editCategoryUrlBase'] . '/' . $row->tid; ?>"><?php echo __('Edit'); ?></a>
                                    <?php 
                                    if (isset($selectedItem) && $selectedItem === true) {
                                        echo '</strong>';
                                        unset($selectedItem);
                                    }
                                    ?> 
                                </li>
                                <?php }// endforeach;
                                unset($row);
                                ?> 
                            </ul>
                            <p><?php 
                            echo d__('rdbcmsa', 'Warning!') . ' ';
                            echo d__('rdbcmsa', 'Child entries of any selected items will also be affected.'); 
                            ?></p>
                            <?php }// endif; ?> 

                            <div class="form-group submit-button-row">
                                <div class="control-wrapper">
                                    <button class="rd-button primary rdba-submit-button warning" type="submit"<?php if (isset($formValidated) && $formValidated === false) {echo ' disabled="disabled"';} ?>><?php echo __('Confirm'); ?></button>
                                </div>
                            </div>
                        </form>