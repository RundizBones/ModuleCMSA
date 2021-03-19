<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header">
                            <?php echo $pageTitle; ?> 
                            <a class="rd-button rdbcmsa-translationmatcher-add-openform" href="#">
                                <i class="fas fa-plus-circle"></i> <?php echo __('Add'); ?>
                            </a>
                        </h1>

                        <form id="translationmatcher-list-form" class="rdba-datatables-form">
                            <div class="form-result-placeholder"></div>
                            <table id="translationMatcherListTable" class="translationMatcherListTable rdba-datatables-js responsive hover" width="100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.translationMatcherListTable'), jQuery(this));"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo d__('rdbcmsa', 'Table'); ?></th>
                                        <?php
                                        if (isset($languages) && is_array($languages)) {
                                            foreach ($languages as $languageId => $languageItems) {
                                                echo '<th class="languageId-' . htmlspecialchars($languageId, ENT_QUOTES) . '">' . ($languageItems['languageName'] ?? $languageId) . '</th>' . PHP_EOL;
                                            }// endforeach;
                                            unset($languageId, $languageItems);
                                        }
                                        ?> 
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th class="column-checkbox"><input type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.translationMatcherListTable'), jQuery(this));"></th>
                                        <th class="rd-hidden"><?php echo __('ID'); ?></th>
                                        <th class="column-primary" data-priority="1"><?php echo d__('rdbcmsa', 'Table'); ?></th>
                                        <?php
                                        if (isset($languages) && is_array($languages)) {
                                            foreach ($languages as $languageId => $languageItems) {
                                                echo '<th class="languageId-' . htmlspecialchars($languageId, ENT_QUOTES) . '">' . ($languageItems['languageName'] ?? $languageId) . '</th>' . PHP_EOL;
                                            }// endforeach;
                                            unset($languageId, $languageItems);
                                        }
                                        ?> 
                                    </tr>
                                </tfoot>
                            </table>
                        </form>


                        <div id="translationmatcher-editing-dialog" class="rd-dialog-modal">
                            <div class="rd-dialog rd-dialog-size-large" aria-labelledby="translationmatcher-editing-dialog-label">
                                <div class="rd-dialog-header">
                                    <h4 id="translationmatcher-editing-dialog-label" class="rd-dialog-title"></h4>
                                    <button class="rd-dialog-close" type="button" aria-label="Close" data-dismiss="dialog">
                                        <i class="fas fa-times" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="rd-dialog-body">
                                    <form id="rdbcmsa-translationmatcher-editing-form" class="rd-form horizontal" method="post">
                                        <div class="form-result-placeholder"></div>
                                        <input id="rdbcmsa-translationmatcher-tm_id" type="hidden" name="tm_id" value="">
                                        <div class="form-group rd-columns-flex-container">
                                            <label class="control-label"><?php echo d__('rdbcmsa', 'Table'); ?></label>
                                            <div class="control-wrapper col-md-4">
                                                <select id="rdbcmsa-translationmatcher-tm_table" name="tm_table" required="">
                                                    <option value="posts">posts</option>
                                                    <option value="taxonomy_term_data">taxonomy_term_data</option>
                                                </select>
                                            </div>
                                        </div>
                                        <?php
                                        if (isset($languages) && is_array($languages)) {
                                            foreach ($languages as $languageId => $languageItems) {
                                        ?> 
                                        <div class="form-group rd-columns-flex-container">
                                            <label class="control-label" for="rdbcmsa-translationmatcher-matches-<?php echo htmlspecialchars($languageId, ENT_QUOTES); ?>"><?php echo ($languageItems['languageName'] ?? $languageId); ?></label>
                                            <div class="control-wrapper col-md-4">
                                                <input 
                                                    id="rdbcmsa-translationmatcher-matches-<?php echo htmlspecialchars($languageId, ENT_QUOTES); ?>" 
                                                    class="rdbcmsa-translationmatcher-matches-input-id"
                                                    type="hidden" 
                                                    name="matches[<?php echo $languageId; ?>]" 
                                                    value=""
                                                >
                                                <input 
                                                    id="rdbcmsa-translationmatcher-matches-<?php echo htmlspecialchars($languageId, ENT_QUOTES); ?>-display" 
                                                    class="rdbcmsa-translationmatcher-matches-input-id-display" 
                                                    type="text" 
                                                    name="prog_matches[<?php echo $languageId; ?>]" 
                                                    value=""
                                                    list="prog_data-result"
                                                    autocomplete="off"
                                                    data-language-id="<?php echo htmlspecialchars($languageId, ENT_QUOTES); ?>" 
                                                >
                                            </div>
                                        </div>
                                        <?php
                                            }// endforeach;
                                            unset($languageId, $languageItems);
                                        }
                                        ?> 
                                        <datalist id="prog_data-result"></datalist>
                                        <div class="form-group submit-button-row">
                                            <label class="control-label"></label>
                                            <div class="control-wrapper">
                                                <button class="rd-button primary" type="submit"><?php echo __('Save'); ?></button>
                                            </div>
                                        </div>
                                    </form>
                                </div><!--.rd-dialog-body-->
                            </div><!--.rd-dialog-->
                        </div><!--.rd-dialog-modal-->


                        <template id="rdba-datatables-row-actions">
                            <div class="row-actions">
                                <span class="action"><?php echo __('ID'); ?> {{tm_id}}</span>
                                <span class="action"><a class="rdbcmsa-translationmatcher-edit-openform" href="#{{tm_id}}" data-tm_id="{{tm_id}}"><?php echo __('Edit'); ?></a></span>
                            </div>
                        </template>

                        <template id="rdba-datatables-result-controls">
                            <div class="col-xs-12 col-sm-6">
                                <label>
                                    <?php echo d__('rdbcmsa', 'Table'); ?>
                                    <select id="rdba-filter-tm_table" name="filter-tm_table">
                                        <option value=""><?php echo __('All'); ?></option>
                                        <option value="posts">posts</option>
                                        <option value="taxonomy_term_data">taxonomy_term_data</option>
                                    </select>
                                </label>
                                <label>
                                    <?php echo __('Search'); ?>
                                    <input id="rdba-filter-search" class="rdba-datatables-input-search" type="search" name="search" aria-control="translationMatcherListTable" placeholder="<?php echo esc_d__('rdbcmsa', 'The ID of data.'); ?>">
                                </label>
                                <div class="rd-button-group">
                                    <button id="rdba-datatables-filter-button" class="rdba-datatables-filter-button rd-button" type="button"><?php echo __('Filter'); ?></button>
                                    <button class="rd-button dropdown-toggler" type="button" data-placement="bottom right">
                                        <i class="fas fa-caret-down"></i>
                                        <span class="sr-only"><?php echo __('More'); ?></span>
                                    </button>
                                    <ul class="rd-dropdown">
                                        <li><a href="#reset" onclick="return RdbCMSATranslationMatcher.resetDataTable();"><?php echo __('Reset'); ?></a></li>
                                    </ul>
                                </div>
                            </div>
                        </template>
                        <template id="rdba-datatables-result-controls-pagination">
                            <span class="rdba-datatables-result-controls-info">
                            {{#ifGE recordsFiltered 2}}
                                {{recordsFiltered}} <?php echo __('items'); ?>
                            {{else}}
                                {{recordsFiltered}} <?php echo __('item'); ?>
                            {{/ifGE}}
                            </span>
                        </template>
                        <template id="rdba-datatables-actions-controls">
                            <div class="col-xs-12 col-sm-6">
                                <label>
                                    <select id="translationmatcher-list-actions" class="translationmatcher-list-actions rdba-actions-selectbox" name="bulk-actions">
                                        <option value=""><?php echo __('Action'); ?></option>
                                        <option value="delete"><?php echo d__('rdbcmsa', 'Delete'); ?></option>
                                    </select>
                                </label>
                                <button id="translationmatcher-list-actions-button" class="rd-button" type="submit"><?php echo __('Apply'); ?></button>
                                <span class="action-status-placeholder"></span>
                            </div>
                        </template>