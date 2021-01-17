<table id="revisionHistoryTable" class="revisionHistoryTable rdba-datatables-js responsive hover" width="100%">
    <thead>
        <tr>
            <th></th>
            <th class="column-checkbox"><input class="rdbcmsa-posts-revision_id-checkbox" type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.revisionHistoryTable'), jQuery(this));"></th>
            <th class="rd-hidden"><?php echo __('ID'); ?></th>
            <th class="column-primary" data-priority="1"><?php echo d__('rdbcmsa', 'Revision log'); ?></th>
            <th><?php echo d__('rdbcmsa', 'Author'); ?></th>
            <th><?php echo d__('rdbcmsa', 'Created date'); ?></th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <th></th>
            <th class="column-checkbox"><input class="rdbcmsa-posts-revision_id-checkbox" type="checkbox" onclick="RdbaCommon.dataTableCheckboxToggler(jQuery('.revisionHistoryTable'), jQuery(this));"></th>
            <th class="rd-hidden"><?php echo __('ID'); ?></th>
            <th class="column-primary" data-priority="1"><?php echo d__('rdbcmsa', 'Revision log'); ?></th>
            <th><?php echo d__('rdbcmsa', 'Author'); ?></th>
            <th><?php echo d__('rdbcmsa', 'Created date'); ?></th>
        </tr>
    </tfoot>
</table>


<div id="rdbcmsa-posts-compare-dialog" class="rd-dialog-modal">
    <div class="rd-dialog rd-dialog-size-fullwindow" aria-labelledby="rdbcmsa-posts-compare-dialog-label">
        <div class="rd-dialog-header">
            <h4 id="rdbcmsa-posts-compare-dialog-label" class="rd-dialog-title"><?php echo d__('rdbcmsa', 'Compare'); ?></h4>
            <button class="rd-dialog-close" type="button" aria-label="Close" data-dismiss="dialog">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="rd-dialog-body">
            <h4><?php echo __('ID'); ?> <span class="compare-revision_id"></span></h4>
            <textarea id="revision_body_value-history" class="rd-hidden"></textarea>
            <textarea id="revision_body_summary-history" class="rd-hidden"></textarea>
            <textarea id="revision_head_value-history" class="rd-hidden"></textarea>
            <div class="revision_body_value-diff diff-views"></div>
            <div class="revision_body_summary-diff diff-views"></div>
            <div class="revision_head_value-diff diff-views"></div>
        </div>
    </div>
</div><!--.rd-dialog-modal-->


<template id="rdba-datatables-row-actions">
    <div class="row-actions">
        <span class="action"><?php echo __('ID'); ?> {{revision_id}}</span>
        {{#ifEquals RdbCMSAPostsEditObject.currentRevisionId revision_id}}
        <div><em><?php echo d__('rdbcmsa', 'Current revision'); ?></em></div>
        {{else}}
        <span class="action"><a class="action-view-compare row-actions-link" data-revision_id="{{revision_id}}"><?php echo d__('rdbcmsa', 'Compare'); ?></a></span>
        <span class="action"><a class="action-rollback row-actions-link" data-revision_id="{{revision_id}}"><?php echo d__('rdbcmsa', 'Rollback'); ?></a></span>
        {{/ifEquals}}
    </div>
</template>


<template id="rdba-datatables-result-controls">
    <div class="col-xs-12 col-sm-6"></div>
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
            <select id="rdbcmsa-posts-revisionhistory-actions" class="rdbcmsa-posts-revisionhistory-actions rdba-actions-selectbox" name="financialtemplates-actions">
                <option value=""><?php echo __('Action'); ?></option>
                <option value="delete"><?php echo __('Delete'); ?></option>
            </select>
        </label>
        <button id="rdbcmsa-posts-revisionhistory-actions-button" class="rd-button" type="button"><?php echo __('Apply'); ?></button>
        <span class="action-status-placeholder"></span>
    </div>
</template>