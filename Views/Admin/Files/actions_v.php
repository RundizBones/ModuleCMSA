<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo $pageTitle; ?></h1>

                        <form id="rdbcmsa-actions-contents-files-form" class="rd-form horizontal rdba-edit-form" method="post" action="">
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
                            <input id="bulk-file_ids" type="hidden" name="file_ids" value="<?php if (isset($file_ids)) {echo htmlspecialchars($file_ids, ENT_QUOTES);} ?>">

                            <h3><?php if (isset($actionText)) {echo $actionText;} ?></h3>
                            <?php if (isset($listSelectedFiles['items']) && is_array($listSelectedFiles['items']) && !empty($listSelectedFiles['items'])) { ?>
                            <ul>
                                <?php foreach ($listSelectedFiles['items'] as $row) { ?> 
                                <li>
                                    <?php echo $row->file_original_name; ?> <a href="<?php echo $urls['editFileUrlBase'] . '/' . $row->file_id; ?>"><?php echo __('Edit'); ?></a>
                                    <?php if (isset($row->totalFoundInPosts)) { ?> 
                                    <br>
                                    <?php printf(dn__('rdbcmsa', 'Found in %d post.', 'Found in %d posts.', $row->totalFoundInPosts), $row->totalFoundInPosts); ?> 
                                    <?php }// endif; $row->totalFoundInPosts ?> 
                                </li>
                                <?php }// endforeach;
                                unset($row);
                                ?> 
                            </ul>
                            <?php 
                                $buttonClass = 'warning';
                                if (isset($action) && $action === 'delete') { 
                                    $buttonClass = 'danger';
                            ?> 
                            <p><?php 
                            echo d__('rdbcmsa', 'Warning!') . ' ';
                            echo d__('rdbcmsa', 'The files that was linked in the posts will have status of 404 (not found error).'); 
                            ?></p>
                            <?php 
                                }// endif; $action === 'delete' 
                            }// endif; $listSelectedFiles ?> 

                            <div class="form-group submit-button-row">
                                <div class="control-wrapper">
                                    <button class="rd-button <?php echo $buttonClass; ?> rdba-submit-button" type="submit"<?php if (isset($formValidated) && $formValidated === false) {echo ' disabled="disabled"';} ?>><?php echo __('Confirm'); ?></button>
                                </div>
                            </div>
                        </form>