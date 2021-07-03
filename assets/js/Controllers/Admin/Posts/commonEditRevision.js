/**
 * Common revision JS on edit pages for "post".
 */


class RdbCMSAPostsCommonEditRevision extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        super(options);

        if (typeof(options) === 'undefined') {
            options = {};
        }

        // the editing object
        if (
            !RdbaCommon.isset(() => options.editingObject) ||
            (RdbaCommon.isset(() => options.editingObject) && _.isEmpty(options.editingObject))
        ) {
            options.editingObject = RdbCMSAPostsEditObject;
        }
        this.editingObject = options.editingObject;

        // the revision history bulk action button ID selector.
        if (
            !RdbaCommon.isset(() => options.revisionHistoryBulkActionButtonSelector) ||
            (RdbaCommon.isset(() => options.revisionHistoryBulkActionButtonSelector) && _.isEmpty(options.revisionHistoryBulkActionButtonSelector))
        ) {
            options.revisionHistoryBulkActionButtonSelector = '#rdbcmsa-posts-revisionhistory-actions-button';
        }
        this.revisionHistoryBulkActionButtonSelector = options.revisionHistoryBulkActionButtonSelector;

        // the revision history bulk action ID selector.
        if (
            !RdbaCommon.isset(() => options.revisionHistoryBulkActionSelector) ||
            (RdbaCommon.isset(() => options.revisionHistoryBulkActionSelector) && _.isEmpty(options.revisionHistoryBulkActionSelector))
        ) {
            options.revisionHistoryBulkActionSelector = '#rdbcmsa-posts-revisionhistory-actions';
        }
        this.revisionHistoryBulkActionSelector = options.revisionHistoryBulkActionSelector;

        // the revision history tab content ID selector.
        if (
            !RdbaCommon.isset(() => options.revisionHistoryTabContentSelector) ||
            (RdbaCommon.isset(() => options.revisionHistoryTabContentSelector) && _.isEmpty(options.revisionHistoryTabContentSelector))
        ) {
            options.revisionHistoryTabContentSelector = '#post-tab-revisionhistory';
        }
        this.revisionHistoryTabContentSelector = options.revisionHistoryTabContentSelector;

        this.editControllerClass;
        this.postCommonClass;

        // below is required for datatable and related tasks. -----------------------
        // datatable ID.
        if (
            !RdbaCommon.isset(() => options.datatableIDSelector) || 
            (RdbaCommon.isset(() => options.datatableIDSelector) && _.isEmpty(options.datatableIDSelector))
        ) {
            options.datatableIDSelector = '#revisionHistoryTable';
        }
        this.datatableIDSelector = options.datatableIDSelector;
        this.activatedDatatable = false;
        // other default values for datatable.
        this.defaultSortOrder = [[2, 'desc']];
        this.currentRevisionId;
    }// constructor


    /**
     * Activate revision history data table.
     * 
     * @private This method was called from `listenRevisionTabActive()` method.
     * @returns {undefined}
     */
    activateRevisionHistoryDatatable() {
        let $ = jQuery.noConflict();
        let thisClass = this;
        let addedCustomResultControls = false;

        // config moment locale to use it later with any dates.
        if (RdbaCommon.isset(() => RdbaUIXhrCommonData.currentLocale)) {
            moment.locale(RdbaUIXhrCommonData.currentLocale);
        }
        let siteTimezone;
        if (RdbaCommon.isset(() => RdbaUIXhrCommonData.configDb.rdbadmin_SiteTimezone)) {
            siteTimezone = RdbaUIXhrCommonData.configDb.rdbadmin_SiteTimezone;
        } else {
            siteTimezone = 'Asia/Bangkok';
        }

        $.when(this.editControllerClass.ajaxGetFormDataPromise)
        .done(function() {
            let dataTable = $(thisClass.datatableIDSelector).DataTable({
                'ajax': {
                    'url': thisClass.editingObject.getPostRevisionHistoryItemsRESTUrlBase + '/' + thisClass.editingObject.post_id,
                    'method': thisClass.editingObject.getPostRevisionHistoryItemsRESTMethod,
                    'dataSrc': 'listItems',// change array key of data source. see https://datatables.net/examples/ajax/custom_data_property.html
                },
                'autoWidth': false,// don't set style="width: xxx;" in the table cell.
                'columnDefs': [
                    {
                        'orderable': false,// make checkbox column not sortable.
                        'searchable': false,// make checkbox column can't search.
                        'targets': [0, 1, 3, 4, 5]
                    },
                    {
                        'className': 'control',
                        'data': 'revision_id',
                        'targets': 0,
                        'render': function () {
                            // make first column render nothing (for responsive expand/collapse button only).
                            // this is for working with responsive expand/collapse column and AJAX.
                            return '';
                        }
                    },
                    {
                        'className': 'column-checkbox',
                        'data': 'revision_id',
                        'targets': 1,
                        'render': function(data, type, row, meta) {
                            let output = '<input class="rdbcmsa-posts-revision_id-checkbox" type="checkbox" name="revision_id[]" value="' + row.revision_id + '"';
                            if (row.revision_id == thisClass.currentRevisionId) {
                                output += ' disabled="disabled"';
                            }
                            output += '>';
                            return output;
                        }
                    },
                    {
                        'data': 'revision_id',
                        'targets': 2,
                        'visible': false
                    },
                    {
                        'data': 'revision_log',
                        'targets': 3,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                            // ifEquals @link https://stackoverflow.com/a/34252942/128761
                            Handlebars.registerHelper('ifEquals', function(arg1, arg2, options) {
                                return (arg1 == arg2) ? options.fn(this) : options.inverse(this);
                            });

                            let template = Handlebars.compile(source);
                            row.RdbCMSAPostsEditObject = thisClass.editingObject;
                            row.RdbCMSAPostsEditObject.currentRevisionId = thisClass.currentRevisionId;
                            if (data === null) {
                                data = '';
                            }
                            let html = RdbaCommon.truncateString(data, 150) + template(row);
                            return html;
                        }
                    },
                    {
                        'data': 'user_id',
                        'targets': 4,
                        'render': function(data, type, row, meta) {
                            return '<a href="' + thisClass.editingObject.editUserPageUrlBase + '/' + data + '" title="' + RdbaCommon.escapeHtml(row.user_display_name) + '">' + RdbaCommon.truncateString(row.user_display_name, 20) + '</a>';
                        }
                    },
                    {
                        'data': 'revision_date',
                        'targets': 5,
                        'render': function(data, type, row, meta) {
                            if (row.revision_date_gmt) {
                                let dateString = '';
                                dateString += '<small>' + moment(row.revision_date_gmt + 'Z').tz(siteTimezone).format('YYYY-M-D HH:mm:ss Z') + '</small><br>';
                                return dateString;
                            } else {
                                return '';
                            }
                        }
                    }
                ],
                'createdRow': function(row, data, dataIndex) {
                    if (data.revision_id === thisClass.currentRevisionId) {
                        $(row).addClass('table-row-info');
                    }
                },
                'dom': thisClass.datatablesDOM,
                'fixedHeader': true,
                'language': datatablesTranslation,// datatablesTranslation is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
                'order': thisClass.defaultSortOrder,
                'pageLength': parseInt(RdbaUIXhrCommonData.configDb.rdbadmin_AdminItemsPerPage),
                'pagingType': 'input',
                'processing': true,
                'responsive': {
                    'details': {
                        'type': 'column',
                        'target': 0
                    }
                },
                'searchDelay': 1300,
                'serverSide': true,
                // state save ( https://datatables.net/reference/option/stateSave ).
                // to use state save, any custom filter should use `stateLoadCallback` and set input value.
                // maybe use keepconditions ( https://github.com/jhyland87/DataTables-Keep-Conditions ).
                'stateSave': false
            });//.DataTable()

            // datatables events
            dataTable.on('xhr.dt', function(e, settings, json, xhr) {
                if (addedCustomResultControls === false) {
                    // if it was not added custom result controls yet.
                    // set additional data.
                    json.RdbCMSAPostsEditObject = thisClass.editingObject;
                    // add search controls.
                    thisClass.addCustomResultControls(json);
                    // add bulk actions controls.
                    thisClass.addActionsControls(json);
                    addedCustomResultControls = true;
                }
                // add pagination.
                thisClass.addCustomResultControlsPagination(json);

                if (json && json.formResultMessage) {
                    RdbaCommon.displayAlertboxFixed(json.formResultMessage, json.formResultStatus);
                }

                if (typeof(json) !== 'undefined' && typeof(json.csrfKeyPair) !== 'undefined') {
                    thisClass.editingObject.csrfKeyPair = json.csrfKeyPair;
                }
            });// datatables on xhr complete.

            console.log('activated datatable.');
            thisClass.activatedDatatable = true;
        });// uiXhrCommonData.done()
    }// activateRevisionHistoryDatatable


    /**
     * Ajax delete revision items.
     * 
     * @private This method was called from `listenRevisionBulkAction()` method.
     * @param {array} checkboxes
     * @returns {undefined}
     */
    ajaxDeleteRevisionItems(checkboxes) {
        let thisClass = this;
        let thisForm = document.querySelector(this.editControllerClass.formIDSelector);
        let submitBtn = thisForm.querySelector(this.revisionHistoryBulkActionButtonSelector);

        // add spinner icon
        thisForm.querySelector('.action-status-placeholder').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
        // lock submit button
        submitBtn.disabled = true;

        let formData = new FormData();
        formData.append(thisClass.editingObject.csrfName, thisClass.editingObject.csrfKeyPair[thisClass.editingObject.csrfName]);
        formData.append(thisClass.editingObject.csrfValue, thisClass.editingObject.csrfKeyPair[thisClass.editingObject.csrfValue]);
        formData.append('action', thisForm.querySelector('#rdbcmsa-posts-revisionhistory-actions').value);
        checkboxes.forEach(function(item, index) {
            formData.append('revision_id[' + index + ']', item);
        });

        RdbaCommon.XHR({
            'url': thisClass.editingObject.deletePostRevisionItemsRESTUrl
                .replace('%post_id%', thisClass.editingObject.post_id),
            'method': thisClass.editingObject.deletePostRevisionItemsRESTMethod,
            'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
            'data': new URLSearchParams(_.toArray(formData)).toString(),// without URLSearchParams.toString, the PHP cannot parse data to custom $_METHOD correctly.
            'dataType': 'json'
        })
        .catch(function(responseObject) {
            // XHR failed.
            let response = responseObject.response;

            if (response && response.formResultMessage) {
                RDTAAlertDialog.alert({
                    'type': RdbaCommon.getAlertClassFromStatus(response.formResultStatus),
                    'html': RdbaCommon.renderAlertContent(response.formResultMessage)
                });
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                thisClass.editingObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.reject(responseObject);
        })
        .then(function(responseObject) {
            // XHR success.
            let response = responseObject.response;

            // reload revision history.
            thisClass.reloadDatatable();

            if (response && response.formResultMessage) {
                RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                thisClass.editingObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.resolve(responseObject);
        })
        .finally(function() {
            // remove loading icon
            thisForm.querySelector('.loading-icon').remove();
            // unlock submit button
            submitBtn.disabled = false;
        });
    }// ajaxDeleteRevisionItems


    /**
     * Iniatialize method.
     * 
     * @returns {undefined}
     */
    init() {
        // listen revision history tab active and hide submit button. this must be listen before activate tabs.
        this.listenRevisionTabActive();

        // listen on enter key for specific field(s) and prevent form submit.
        this.listenRevisionOnEnterPreventSubmit();
        // listen bulk action clicked.
        this.listenRevisionBulkAction();
        // listen compare link clicked.
        this.listenRevisionCompareLink();
        // listen rollback link clicked.
        this.listenRevisionRollback();
    }// init


    /**
     * Listen revision history bulk action clicked.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenRevisionBulkAction() {
        let thisClass = this;
        let thisForm = document.querySelector(this.editControllerClass.formIDSelector);

        thisForm.addEventListener('click', function(event) {
            if (
                RdbaCommon.isset(() => event.target.id) && 
                '#' + event.target.id === thisClass.revisionHistoryBulkActionButtonSelector
            ) {
                event.preventDefault();

                // validate form
                let formValidated = false;
                // validate selected checkbox item
                let checkboxes = [];
                thisForm.querySelectorAll('input[type="checkbox"][name="revision_id[]"]:checked').forEach(function(item, index) {
                    checkboxes.push(item.value);
                });
                if (checkboxes.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': thisClass.editingObject.txtPleaseSelectAtLeastOne,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                let selectAction = thisForm.querySelector(thisClass.revisionHistoryBulkActionSelector);
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': thisClass.editingObject.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    }
                }

                if (formValidated === true) {
                    if (selectAction.value === 'delete' && confirm(thisClass.editingObject.txtConfirmDelete)) {
                        thisClass.ajaxDeleteRevisionItems(checkboxes);
                    }
                }// endif; formValidated
            }// endif; event.target.id is bulk action button
        });
    }// listenRevisionBulkAction


    /**
     * Listen revision history compare link clicked.
     * 
     * @private Thism method was called from `init()` method.
     * @returns {undefined}
     */
    listenRevisionCompareLink() {
        let tabRevisionHistoryContent = document.querySelector(this.revisionHistoryTabContentSelector);
        let thisForm = document.querySelector(this.editControllerClass.formIDSelector);
        let thisClass = this;
        let thisDialogSelector = '#rdbcmsa-posts-compare-dialog';

        tabRevisionHistoryContent.addEventListener('click', function(event) {
            if (RdbaCommon.isset(() => event.target.classList) && event.target.classList.contains('action-view-compare')) {
                event.preventDefault();

                let thisActionLink = event.target;
                let linkData = event.target.dataset;

                if (linkData && linkData.revision_id) {
                    // if contain data-revision_id="xxx"

                    // add spinner icon
                    thisActionLink.insertAdjacentHTML('beforeend', ' <i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');

                    // start working action link.
                    RdbaCommon.XHR({
                        'url': thisClass.editingObject.getPostRevisionContentRESTUrl
                            .replace('%post_id%', thisClass.editingObject.post_id)
                            .replace('%revision_id%', linkData.revision_id),
                        'method': thisClass.editingObject.actionViewSourceRESTMethod,
                        'dataType': 'json'
                    })
                    .then(function(responseObject) {
                        // XHR success.
                        let response = responseObject.response;

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            thisClass.editingObject.csrfKeyPair = response.csrfKeyPair;
                        }

                        if (response.result) {
                            let thisDialogBody = document.querySelector(thisDialogSelector + ' .rd-dialog-body');
                            thisDialogBody.querySelector('.compare-revision_id').innerHTML = linkData.revision_id;

                            // loop set value to textarea of history for compare.
                            for (let prop in response.result) {
                                let historyContent = thisDialogBody.querySelector('#' + prop + '-history');
                                if (historyContent) {
                                    historyContent.value = response.result[prop];
                                    let propElement = thisForm.querySelector('#' + prop);
                                    if (propElement === '' || propElement === null) {
                                        continue;
                                    }
                                    let currentContentValue = propElement.value;

                                    // set file name for js diff to functional.
                                    let filename = '';
                                    if (
                                        _.includes(prop, 'head_value') || 
                                        _.includes(prop, 'body_value') || 
                                        _.includes(prop, 'body_summary')
                                    ) {
                                        let labelText = thisForm.querySelector('label[for="' + prop + '"]');
                                        filename = labelText.textContent;
                                    }

                                    // create unified diff using "jsdiff" node package.
                                    let thisDiff = Diff.createPatch(filename, historyContent.value, currentContentValue);

                                    // render diff in pretty result using "diff2html".
                                    let targetRender = thisDialogBody.querySelector('.' + prop + '-diff');
                                    let diff2HtmlConfig = {
                                        diffMaxChanges: 200,
                                        drawFileList: false, 
                                        fileContentToggle: false,
                                        highlight: true, 
                                        matching: 'lines', 
                                        outputFormat: 'side-by-side'
                                    };
                                    let diff2HtmlUi = new Diff2HtmlUI(targetRender, thisDiff, diff2HtmlConfig);
                                    diff2HtmlUi.synchronisedScroll();
                                    diff2HtmlUi.draw();
                                }// endif;
                            }// endfor;

                            // activate dialog.
                            let rdtaDialog = new RDTADialog();
                            rdtaDialog.activateDialog(thisDialogSelector);
                        }

                        return Promise.resolve(responseObject);
                    })
                    .catch(function(responseObject) {
                        // XHR failed.
                        let response = responseObject.response;
                        console.error(responseObject);

                        if (response && response.formResultMessage) {
                            RDTAAlertDialog.alert({
                                'type': 'danger',
                                'text': response.formResultMessage
                            });
                        }

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            thisClass.editingObject.csrfKeyPair = response.csrfKeyPair;
                        }

                        return Promise.reject(responseObject);
                    })
                    .finally(function() {
                        // remove loading icon
                        thisActionLink.querySelector('.loading-icon').remove();
                    });
                }
            }// endif; clicked on selected link.
        });
    }// listenRevisionCompareLink


    /**
     * Listen on enter key on revision elements and prevent form submit.
     * 
     * @private Thism method was called from `init()` method.
     * @returns {undefined}
     */
    listenRevisionOnEnterPreventSubmit() {
        document.addEventListener('keydown', function(event) {
            if (
                (
                    RdbaCommon.isset(() => event.target.classList) &&
                    (
                        event.target.classList.contains('rdbcmsa-posts-revision_id-checkbox') ||
                        event.target.classList.contains('rdbcmsa-posts-revisionhistory-actions') 
                    )
                ) &&
                event.key.toLowerCase() == 'enter'
            ) {
                event.preventDefault();
                console.log('prevented form submit.');
            }
        });
    }// listenRevisionOnEnterPreventSubmit


    /**
     * Listen rollback link clicked and confirmed before do rollback to selected revision.
     * 
     * @private This method was called from `staticInit()` method.
     * @returns {undefined}
     */
    listenRevisionRollback() {
        let tabRevisionHistoryContent = document.querySelector(this.revisionHistoryTabContentSelector);
        let thisClass = this;
        let currentDisableRollbackLink = false;
        let thisForm = document.querySelector(this.editControllerClass.formIDSelector);

        tabRevisionHistoryContent.addEventListener('click', function(event) {
            if (RdbaCommon.isset(() => event.target.classList) && event.target.classList.contains('action-rollback')) {
                event.preventDefault();

                if (currentDisableRollbackLink === false) {
                    // set disabled class to all rollback links.
                    document.querySelectorAll('.action-rollback').forEach(function(item, index) {
                        item.classList.add('disabled');
                    });
                    currentDisableRollbackLink = true;

                    let confirmVal = confirm(thisClass.editingObject.txtAreYouSureRollback);

                    if (confirmVal === true) {
                        // if confirmed.
                        let thisActionLink = event.target;
                        let linkData = event.target.dataset;

                        if (linkData && linkData.revision_id) {
                            // add spinner icon
                            thisActionLink.insertAdjacentHTML('beforeend', ' <i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');

                            let formData = new FormData();
                            formData.append(thisClass.editingObject.csrfName, thisClass.editingObject.csrfKeyPair[thisClass.editingObject.csrfName]);
                            formData.append(thisClass.editingObject.csrfValue, thisClass.editingObject.csrfKeyPair[thisClass.editingObject.csrfValue]);
                            formData.append('revision_id', linkData.revision_id);

                            RdbaCommon.XHR({
                                'url': thisClass.editingObject.postRollbackRevisionRESTUrl
                                    .replace('%post_id%', thisClass.editingObject.post_id)
                                    .replace('%revision_id%', linkData.revision_id),
                                'method': thisClass.editingObject.postRollbackRevisionRESTMethod,
                                'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                                'data': new URLSearchParams(_.toArray(formData)).toString(),// without URLSearchParams.toString, the PHP cannot parse data to custom $_METHOD correctly.
                                'dataType': 'json'
                            })
                            .catch(function(responseObject) {
                                // XHR failed.
                                let response = responseObject.response;
                                console.error(responseObject);

                                if (response && response.formResultMessage) {
                                    RDTAAlertDialog.alert({
                                        'type': 'danger',
                                        'text': response.formResultMessage
                                    });
                                }

                                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                                    thisClass.editingObject.csrfKeyPair = response.csrfKeyPair;
                                }

                                return Promise.reject(responseObject);
                            })
                            .then(function(responseObject) {
                                // XHR success.
                                let response = responseObject.response;

                                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                                    thisClass.editingObject.csrfKeyPair = response.csrfKeyPair;
                                }

                                if (typeof(response.formResultMessage) !== 'undefined') {
                                    RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                                }

                                if (typeof(response.rollbackSuccess) !== 'undefined' && response.rollbackSuccess === true) {
                                    // if rollback success.
                                    thisClass.postCommonClass.reloadFormData();
                                }// endif rollback success.

                                return Promise.resolve(responseObject);
                            })
                            .finally(function() {
                                // remove loading icon
                                thisActionLink.querySelector('.loading-icon').remove();
                                // cancel disabled class on all rollback links.
                                cancelDisabledLink();
                            });// end XHR
                        } else {
                            // cancel disabled class on all rollback links.
                            cancelDisabledLink();
                        }// endif; linkData && linkData.revision_id
                    } else {
                        // if not confirmed.
                        // cancel disabled class on all rollback links.
                        cancelDisabledLink();
                    }// endif; confirmVal
                } else {
                    console.log('please wait while rollback other revision.');
                }// endif; currentDisableRollbackLink
            }// endif; clicked on selected link.
        });

        function cancelDisabledLink() {
            document.querySelectorAll('.action-rollback').forEach(function(item, index) {
                item.classList.remove('disabled');
            });
            currentDisableRollbackLink = false;
        }// cancelDisabledLink
    }// listenRevisionRollback


    /**
     * Listen on revision tab active and hide main form submit (save) button.
     * 
     * @private Thism method was called from `init()` method.
     * @returns {undefined}
     */
    listenRevisionTabActive() {
        let thisClass = this;
        let activedDatatable = false;

        document.querySelector(thisClass.editControllerClass.editingTabsSelector).addEventListener('rdta.tabs.activeTab', function(event) {
            if (event.detail && event.detail.targetTab === thisClass.revisionHistoryTabContentSelector) {
                event.preventDefault();
                console.log('revision history tab is currently active.');

                // hide save button
                document.querySelector(thisClass.editControllerClass.formIDSelector + ' .rdbcmsa-contents-posts-save-container').classList.add('rd-hidden');

                if (activedDatatable === false) {
                    // activate revision history data table.
                    thisClass.activateRevisionHistoryDatatable();
                    // mark actived.
                    activedDatatable = true;
                }
            } else {
                // show save button
                document.querySelector(thisClass.editControllerClass.formIDSelector + ' .rdbcmsa-contents-posts-save-container').classList.remove('rd-hidden');
            }
        });
    }// listenRevisionTabActive


    /**
     * Reload datatable.
     * 
     * @private This method was called from `ajaxDeleteRevisionItems()`, `listenRevisionRollback()` methods.
     * @returns {undefined}
     */
    reloadDatatable() {
        jQuery(this.datatableIDSelector).DataTable().order(this.defaultSortOrder).search('').draw();
    }// reloadDatatable


}// RdbCMSAPostsCommonEditRevision