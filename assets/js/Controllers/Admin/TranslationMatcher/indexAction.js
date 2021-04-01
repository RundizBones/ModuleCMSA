/**
 * Translation matcher JS for its controller.
 * 
 * @since 0.0.2
 */


class RdbCMSATranslationMatcher extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        super(options);
        this.dialogIDSelector = '#translationmatcher-editing-dialog';

        this.formIDSelector = '#translationmatcher-list-form';
        this.datatableIDSelector = '#translationMatcherListTable';
        this.defaultSortOrder = [[2, 'desc']];

        this.dataTableColumns = [];
    }// constructor


    /**
     * Activate data table.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    activateDataTable() {
        let $ = jQuery.noConflict();
        let thisClass = this;
        let addedCustomResultControls = false;

        $.when(uiXhrCommonData)// uiXhrCommonData is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
        .then(function() {
            return thisClass.buildColumns();
        })
        .done(function() {
            let dataTable = $(thisClass.datatableIDSelector).DataTable({
                'ajax': {
                    'url': RdbCMSATranslationMatcherIndexObject.getTranslationMatchRESTUrl,
                    'method': RdbCMSATranslationMatcherIndexObject.getTranslationMatchRESTMethod,
                    'dataSrc': 'listItems',// change array key of data source. see https://datatables.net/examples/ajax/custom_data_property.html
                    'data': function(data) {
                        data['filter-tm_table'] = ($('#rdba-filter-tm_table').val() ? $('#rdba-filter-tm_table').val() : '');
                    }
                },
                'autoWidth': false,// don't set style="width: xxx;" in the table cell.
                'columnDefs': thisClass.dataTableColumns,
                'dom': thisClass.datatablesDOM,
                'fixedHeader': true,
                'language': datatablesTranslation,// datatablesTranslation is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
                'order': thisClass.defaultSortOrder,
                'pageLength': parseInt(RdbaUIXhrCommonData.configDb.rdbadmin_AdminItemsPerPage),
                'paging': true,
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
                    json.RdbCMSATranslationMatcherIndexObject = RdbCMSATranslationMatcherIndexObject;
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

                if (json) {
                    if (typeof(json.csrfKeyPair) !== 'undefined') {
                        RdbCMSATranslationMatcherIndexObject.csrfKeyPair = json.csrfKeyPair;
                    }
                }
            })// datatables on xhr complete.
            .on('draw', function() {
                // add listening events.
                thisClass.addCustomResultControlsEvents(dataTable);
            })// datatables on draw complete.
            ;
        });// uiXhrCommonData.done()
    }// activateDataTable


    /**
     * Build data table dynamic columns.
     * 
     * @private This method was called from `activateDataTable()`.
     * @returns {undefined}
     */
    buildColumns() {
        let thisClass = this;
        return new Promise((resolve, reject) => {
            thisClass.dataTableColumns = [];
            let columns = [];
            columns[0] = {
                'className': 'control',
                'data': 'tm_id',
                'orderable': false,
                'searchable': false,
                'targets': 0,
                'render': function () {
                    // make first column render nothing (for responsive expand/collapse button only).
                    // this is for working with responsive expand/collapse column and AJAX.
                    return '';
                }
            };
            columns[1] = {
                'className': 'column-checkbox',
                'data': 'tm_id',
                'orderable': false,
                'searchable': false,
                'targets': 1,
                'render': function(data, type, row, meta) {
                    return '<input type="checkbox" name="tm_id[]" value="' + row.tm_id + '">';
                }
            };
            columns[2] = {
                'data': 'tm_id',
                'targets': 2,
                'visible': false
            };
            columns[3] = {
                'data': 'tm_table',
                'targets': 3,
                'render': function(data, type, row, meta) {
                    let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                    let template = Handlebars.compile(source);
                    row.RdbCMSATranslationMatcherIndexObject = RdbCMSATranslationMatcherIndexObject;

                    let html = '<a class="rdbcmsa-translationmatcher-edit-openform" href="#' + row.tm_id + '" data-tm_id="' + row.tm_id + '">' + data + '</a>';
                    html += template(row);
                    return html;
                }
            };

            // below is dynamic columns depend on languages.
            let dynamicColumnStart = 4;
            if (RdbCMSATranslationMatcherIndexObject.languages) {
                for (const languageId in RdbCMSATranslationMatcherIndexObject.languages) {
                    columns[dynamicColumnStart] = {
                        'data': 'matches',
                        'targets': dynamicColumnStart,
                        'render': function(data, type, row, meta) {
                            let jsonMatches = JSON.parse(data);
                            if (jsonMatches[languageId]) {
                                let data_id = jsonMatches[languageId];
                                return '(' + data_id + ') '
                                    + jsonMatches['data_id' + data_id].data_name + ' '
                                    + '<small><em> &mdash; ' + jsonMatches['data_id' + data_id].data_type + '</em></small> '
                                ;
                            } else {
                                return '';
                            }
                        }
                    };
                    dynamicColumnStart++;
                }
            }

            // set defined columns to class property to use later.
            thisClass.dataTableColumns = columns;
            // done.
            resolve(columns);
        });
    }// buildColumns


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        // activate data table.
        this.activateDataTable();

        // Listn on click add button to open new dialog.
        this.listenClickAddOpenForm();
        // Listn on click edit link to open new dialog.
        this.listenClickEditOpenForm();

        // Listen on keyup and find data ID.
        this.listenKeyupFindDataId();
        this.listenOnChangeAutocomplete();

        // Listen editing form submit and make ajax request.
        this.listenEditingFormSubmit();

        // Listen bulk actions form submit and make ajax request.
        this.listenBulkActionsFormSubmit();
    }// init


    /**
     * Listen bulk actions form submit and make ajax request.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenBulkActionsFormSubmit() {
        let thisClass = this;

        document.addEventListener('submit', function(event) {
            if (event.target && '#' + event.target.id === thisClass.formIDSelector) {
                event.preventDefault();
                let thisForm = event.target;

                // validate selected item.
                let formValidated = false;
                let itemIdsArray = [];
                thisForm.querySelectorAll('input[type="checkbox"][name="tm_id[]"]:checked').forEach(function(item, index) {
                    itemIdsArray.push(item.value);
                });
                if (itemIdsArray.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbCMSATranslationMatcherIndexObject.txtPleaseSelectAtLeastOne,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                let selectAction = thisForm.querySelector('#translationmatcher-list-actions');
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': RdbCMSATranslationMatcherIndexObject.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    } else {
                        formValidated = true;
                    }
                }

                if (formValidated === true) {
                    // if form validated.
                    if (selectAction.value === 'delete') {
                        // if action is delete.
                        // ajax permanently delete post here.
                        thisClass.listenBulkActionsFormSubmitDelete(itemIdsArray);
                    }
                }
            }// endif event id is matched form.
        });
    }// listenBulkActionsFormSubmit


    /**
     * Ajax delete items.
     * 
     * @private This method was called from `listenBulkActionsFormSubmit()`.
     * @param {array} itemIdsArray
     * @returns {undefined}
     */
    listenBulkActionsFormSubmitDelete(itemIdsArray) {
        if (!_.isArray(itemIdsArray)) {
            console.error('The IDs are not array.');
            return false;
        }

        let thisClass = this;
        let thisForm = document.querySelector(this.formIDSelector);
        let submitBtn = thisForm.querySelector('button[type="submit"]');
        let confirmValue = confirm(RdbCMSATranslationMatcherIndexObject.txtConfirmDelete);

        let formData = new FormData(thisForm);
        formData.append(RdbCMSATranslationMatcherIndexObject.csrfName, RdbCMSATranslationMatcherIndexObject.csrfKeyPair[RdbCMSATranslationMatcherIndexObject.csrfName]);
        formData.append(RdbCMSATranslationMatcherIndexObject.csrfValue, RdbCMSATranslationMatcherIndexObject.csrfKeyPair[RdbCMSATranslationMatcherIndexObject.csrfValue]);

        if (confirmValue === true) {
            // reset form result placeholder
            thisForm.querySelector('.form-result-placeholder').innerHTML = '';
            // add spinner icon
            thisForm.querySelector('.action-status-placeholder').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
            // lock submit button
            submitBtn.disabled = true;

            RdbaCommon.XHR({
                'url': RdbCMSATranslationMatcherIndexObject.deleteTranslationMatchRESTUrlBase + '/' + itemIdsArray.join(','),
                'method': RdbCMSATranslationMatcherIndexObject.deleteTranslationMatchRESTMethod,
                'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                'data': new URLSearchParams(_.toArray(formData)).toString(),
                'dataType': 'json'
            })
            .catch(function(responseObject) {
                // XHR failed.
                let response = responseObject.response;

                if (response && response.formResultMessage) {
                    RDTAAlertDialog.alert({
                        'type': 'danger',
                        'text': response.formResultMessage
                    });
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbCMSATranslationMatcherIndexObject.csrfKeyPair = response.csrfKeyPair;
                }

                return Promise.reject(responseObject);
            })
            .then(function(responseObject) {
                // XHR success.
                let response = responseObject.response;

                // reload datatable.
                thisClass.reloadDataTable();

                if (typeof(response) !== 'undefined') {
                    RdbaCommon.displayAlertboxFixed(RdbCMSATranslationMatcherIndexObject.txtDeletedSuccessfully, 'success');
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbCMSATranslationMatcherIndexObject.csrfKeyPair = response.csrfKeyPair;
                }

                return Promise.resolve(responseObject);
            })
            .finally(function() {
                // remove loading icon
                thisForm.querySelector('.loading-icon').remove();
                // unlock submit button
                submitBtn.disabled = false;
            });
        }// endif confirmValue;
    }// listenBulkActionsFormSubmitDelete


    /**
     * Listn on click add button to open new dialog.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenClickAddOpenForm() {
        let thisClass = this;
        let addOpenFormButton = document.querySelector('.rdbcmsa-translationmatcher-add-openform');
        if (addOpenFormButton) {
            addOpenFormButton.addEventListener('click', (event) => {
                event.preventDefault();

                RdbCMSATranslationMatcherIndexObject.editingMode = 'add';
                // set dialog header and reset editing form to its default values.
                document.getElementById('translationmatcher-editing-dialog-label').innerText = RdbCMSATranslationMatcherIndexObject.txtAddNew;
                thisClass.resetEditingForm();
                (new RDTADialog).activateDialog(thisClass.dialogIDSelector);
            });
        }
    }// listenClickAddOpenForm


    /**
     * Listn on click edit link to open new dialog.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenClickEditOpenForm() {
        let thisClass = this;
        const editLinkClass = 'rdbcmsa-translationmatcher-edit-openform';
        document.addEventListener('click', (event) => {
            if (event.currentTarget.activeElement) {
                let thisLink = event.currentTarget.activeElement;
                if (thisLink.classList.contains(editLinkClass)) {
                    event.preventDefault();

                    RdbCMSATranslationMatcherIndexObject.editingMode = 'edit';
                    // set dialog header and reset editing form to its default values.
                    document.getElementById('translationmatcher-editing-dialog-label').innerText = RdbCMSATranslationMatcherIndexObject.txtEdit;
                    thisClass.resetEditingForm();

                    // ajax get form data and render to the form fields.
                    RdbaCommon.XHR({
                        'url': RdbCMSATranslationMatcherIndexObject.getATranslationMatchRESTUrlBase + '/' + thisLink.dataset.tm_id,
                        'method': RdbCMSATranslationMatcherIndexObject.getATranslationMatchRESTMethod,
                        'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'dataType': 'json'
                    })
                    .then(function(responseObject) {
                        // XHR success.
                        let response = responseObject.response;

                        // render data to form fields.
                        let thisForm = document.getElementById('rdbcmsa-translationmatcher-editing-form');
                        thisForm.querySelector('#rdbcmsa-translationmatcher-tm_id').value = thisLink.dataset.tm_id;
                        thisForm.querySelector('#rdbcmsa-translationmatcher-tm_table').value = response.result.tm_table;
                        let matches = JSON.parse(response.result.matches);
                        for (const languageId in RdbCMSATranslationMatcherIndexObject.languages) {
                            let inputDataId = thisForm.querySelector('#rdbcmsa-translationmatcher-matches-' + languageId);
                            let inputDataDisplay = thisForm.querySelector('#rdbcmsa-translationmatcher-matches-' + languageId + '-display');
                            if (matches[languageId]) {
                                let thisDataId = matches[languageId];
                                inputDataId.value = thisDataId;
                                let matchesDataId = matches['data_id' + thisDataId];
                                inputDataDisplay.value = '(' + thisDataId + ') ' + matchesDataId.data_name + ' - ' + matchesDataId.data_type;
                            }
                        }// endfor;
                    }, function(error) {
                        // prevent Uncaught (in promise) error.
                        return Promise.reject(error);
                    })
                    .then(function(responseObject) {
                        // activate dialog.
                        (new RDTADialog).activateDialog(thisClass.dialogIDSelector);
                    }, function(error) {
                        // prevent Uncaught (in promise) error.
                        return Promise.reject(error);
                    })
                    .catch(function(responseObject) {
                        // XHR failed.
                        console.error('XHR error', responseObject);
                    });
                }
            }
        });
    }// listenClickEditOpenForm


    /**
     * Listen editing form submit and make ajax request.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenEditingFormSubmit() {
        let thisClass = this;
        let thisForm = document.getElementById('rdbcmsa-translationmatcher-editing-form');
        let submitBtn = thisForm.querySelector('button[type="submit"]');
        let ajaxSubmitting = false;
        if (thisForm) {
            thisForm.addEventListener('submit', (event) => {
                event.preventDefault();

                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                submitBtn.disabled = true;

                let ajaxFormUrl = '';
                let ajaxFormMethod = 'POST';
                if (RdbCMSATranslationMatcherIndexObject.editingMode === 'add') {
                    // if it is adding new data.
                    ajaxFormUrl = RdbCMSATranslationMatcherIndexObject.addTranslationMatchRESTUrl;
                } else {
                    // if it is editing exists data.
                    ajaxFormUrl = RdbCMSATranslationMatcherIndexObject.editTranslationMatchRESTUrlBase + '/' + thisForm.querySelector('#rdbcmsa-translationmatcher-tm_id').value;
                    ajaxFormMethod = RdbCMSATranslationMatcherIndexObject.editTranslationMatchRESTMethod;
                }

                let formData = new FormData(thisForm);
                formData.append(RdbCMSATranslationMatcherIndexObject.csrfName, RdbCMSATranslationMatcherIndexObject.csrfKeyPair[RdbCMSATranslationMatcherIndexObject.csrfName]);
                formData.append(RdbCMSATranslationMatcherIndexObject.csrfValue, RdbCMSATranslationMatcherIndexObject.csrfKeyPair[RdbCMSATranslationMatcherIndexObject.csrfValue]);

                if (ajaxSubmitting === false) {
                    ajaxSubmitting = true;
                    // ajax submit form.
                    RdbaCommon.XHR({
                        'url': ajaxFormUrl,
                        'method': ajaxFormMethod,
                        'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'data': new URLSearchParams(_.toArray(formData)).toString(),
                        'dataType': 'json'
                    })
                    .then(function(responseObject) {
                        // XHR success.
                        let response = responseObject.response;

                        if (response.savedSuccess === true) {
                            // if saved successfully.
                            // reset form.
                            thisClass.resetEditingForm();
                            // this is opening in dialog, close the dialog and reload page.
                            document.querySelector(thisClass.dialogIDSelector + ' [data-dismiss="dialog"]').click();
                            // reload datatable.
                            jQuery(thisClass.datatableIDSelector).DataTable().ajax.reload(null, false);
                        } 

                        if (response && response.formResultMessage) {
                            // if there is form result message, display it.
                            RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                        }

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            RdbCMSATranslationMatcherIndexObject.csrfKeyPair = response.csrfKeyPair;
                        }

                        return Promise.resolve(responseObject);
                    }, function(error) {
                        // prevent Uncaught (in promise) error.
                        return Promise.reject(error);
                    })
                    .catch(function(responseObject) {
                        // XHR failed.
                        let response = responseObject.response;

                        if (response && response.formResultMessage) {
                            let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                            let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                            thisForm.querySelector('.form-result-placeholder').innerHTML = alertBox;
                        }

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            RdbCMSATranslationMatcherIndexObject.csrfKeyPair = response.csrfKeyPair;
                        }

                        return Promise.reject(responseObject);
                    })
                    .finally(function() {
                        // remove loading icon
                        let loadingIcon = thisForm.querySelector('.loading-icon');
                        if (loadingIcon) {
                            loadingIcon.remove();
                        }
                        // unlock submit button
                        submitBtn.disabled = false;
                        // restore ajax submitting.
                        ajaxSubmitting = false;
                    });
                }// endif; ajaxSubmitting
            });// event listener
        }
    }// listenEditingFormSubmit


    /**
     * Listen on keyup and find data ID.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenKeyupFindDataId() {
        let thisClass = this;
        let matchesInputId = document.querySelectorAll('.rdbcmsa-translationmatcher-matches-input-id-display');
        let tmId = document.querySelector('#rdbcmsa-translationmatcher-editing-form #rdbcmsa-translationmatcher-tm_id');
        let tmTable = document.querySelector('#rdbcmsa-translationmatcher-editing-form #rdbcmsa-translationmatcher-tm_table');

        if (matchesInputId) {
            matchesInputId.forEach((item, index) => {
                item.addEventListener(
                    'keyup', 
                    RdsUtils.delay(function(event) {
                        let inputIdHidden = item.previousElementSibling;
                        let dataResultList = document.getElementById('prog_data-result');
                        inputIdHidden.value = '';
                        dataResultList.innerHTML = '';

                        RdbaCommon.XHR({
                            'url': RdbCMSATranslationMatcherIndexObject.searchEditingTranslationMatchRESTUrl + '?tm_table=' + tmTable.value 
                                + '&prog_data-input=' + item.value 
                                + '&prog_language-id=' + item.dataset.languageId
                                + '&editingMode=' + RdbCMSATranslationMatcherIndexObject.editingMode
                                + '&tm_id=' + tmId.value,
                            'method': RdbCMSATranslationMatcherIndexObject.searchEditingTranslationMatchRESTMethod,
                            'dataType': 'json'
                        })
                        .then(function(responseObject) {
                            // XHR success.
                            let response = responseObject.response;

                            if (RdbaCommon.isset(() => response.items)) {
                                response.items.forEach((item, index) => {
                                    // option value must contain everything user typed or it won't show up.
                                    // example: user type 1 but result in value is "my post" then it won't show up. it must be "(1) my post".
                                    let option = '<option data-data_name="' + item.data_name + '" data-data_id="' + item.data_id + '" value="(' + item.data_id + ') ' + item.data_name + ' - ' + item.data_type + '"></option>';
                                    dataResultList.insertAdjacentHTML('beforeend', option);
                                });
                            }

                            return Promise.resolve(responseObject);
                        }, function(error) {
                            // prevent Uncaught (in promise) error.
                            return Promise.reject(error);
                        })
                        .catch(function(responseObject) {
                            // XHR failed.
                            let response = responseObject.response;

                            console.error(responseObject);
                        });
                    }, 500)
                );
            });
        }
    }// listenKeyupFindDataId


    /**
     * Listen on change (on select) auto complete value of find tag field and make the custom result from datalist.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenOnChangeAutocomplete() {
        document.addEventListener('input', function(event) {
            if (
                RdbaCommon.isset(() => event.target.classList) &&
                event.target.classList.contains('rdbcmsa-translationmatcher-matches-input-id-display')
            ) {
                event.preventDefault();
                let thisInput = event.target;
                let selectedValue = event.target.value;// <option value="xxx">
                let selectedDatalist = document.querySelector('#prog_data-result option[value="' + selectedValue + '"]');
                let inputIdHidden = thisInput.previousElementSibling;
                if (selectedDatalist) {
                    thisInput.value = selectedValue;
                    inputIdHidden.value = selectedDatalist.dataset.data_id;
                }
            }
        });
    }// listenOnChangeAutocomplete


    /**
     * Reload data table and filters.
     * 
     * @private This method was called from `listenFormSubmitAjaxBulkActions()`, `listenFormSubmitAjaxDelete()` methods.
     * @returns {undefined}
     */
    reloadDataTable() {
        let thisClass = this;

        jQuery(thisClass.datatableIDSelector).DataTable().ajax.reload(null, false);
    }// reloadDataTable


    /**
     * Reset data tables.
     * 
     * Call from HTML button.<br>
     * Example: <pre>
     * &lt;button onclick=&quot;return ThisClassName.resetDataTable();&quot;&gt;Reset&lt;/button&gt;
     * </pre>
     * 
     * @returns {false}
     */
    static resetDataTable() {
        let $ = jQuery.noConflict();
        let thisClass = new this();

        // reset form
        document.getElementById('rdba-filter-tm_table').value = '';
        document.getElementById('rdba-filter-search').value = '';

        // datatables have to call with jQuery.
        $(thisClass.datatableIDSelector).DataTable().order(thisClass.defaultSortOrder).search('').draw();// .order must match in columnDefs.

        return false;
    }// resetDataTable


    /**
     * Reset editing form (in dialog) to its default values.
     * 
     * @private This method was called from `listenClickAddOpenForm()`, `listenClickEditOpenForm()`, `listenEditingFormSubmit()`.
     * @returns {undefined}
     */
    resetEditingForm() {
        let thisForm = document.getElementById('rdbcmsa-translationmatcher-editing-form');
        thisForm.querySelector('.form-result-placeholder').innerHTML = '';
        thisForm.querySelector('#rdbcmsa-translationmatcher-tm_id').value = '';
        thisForm.querySelector('#rdbcmsa-translationmatcher-tm_table').value = 'posts';

        thisForm.querySelectorAll('.rdbcmsa-translationmatcher-matches-input-id')
        .forEach((item, index) => {
                item.value = '';
        });
        thisForm.querySelectorAll('.rdbcmsa-translationmatcher-matches-input-id-display')
        .forEach((item, index) => {
            item.value = '';
        });
        thisForm.querySelector('#prog_data-result').innerHTML = '';

        let loadingIcon = thisForm.querySelector('.submit-button-row .loading-icon');
        if (loadingIcon) {
            loadingIcon.remove();
        }
    }// resetEditingForm


}// RdbCMSATranslationMatcher


document.addEventListener('DOMContentLoaded', function() {
    let rdbCMSATranslationMatcherClass = new RdbCMSATranslationMatcher();

    // initialize datatables.
    rdbCMSATranslationMatcherClass.init();
}, false);