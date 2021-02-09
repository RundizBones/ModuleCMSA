/**
 * Tags managenent page JS for its controller.
 */


class RdbCMSATagsIndexController extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        super(options);

        this.formIDSelector = '#tags-list-form';
        this.datatableIDSelector = '#tagsListItemsTable';
        this.defaultSortOrder = [[2, 'desc']];
    }// constructor


    /**
     * Activate data table.
     * 
     * @returns {undefined}
     */
    activateDataTable() {
        let $ = jQuery.noConflict();
        let thisClass = this;
        let addedCustomResultControls = false;

        let urlParams = new URLSearchParams(window.location.search);

        $.when(uiXhrCommonData)// uiXhrCommonData is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
        .done(function() {
            let dataTable = $(thisClass.datatableIDSelector).DataTable({
                'ajax': {
                    'url': RdbCMSATagsIndexObject.getTagsRESTUrl,
                    'method': RdbCMSATagsIndexObject.getTagsRESTMethod,
                    'dataSrc': 'listItems'// change array key of data source. see https://datatables.net/examples/ajax/custom_data_property.html
                },
                'autoWidth': false,// don't set style="width: xxx;" in the table cell.
                'columnDefs': [
                    {
                        'orderable': false,// make checkbox column not sortable.
                        'searchable': false,// make checkbox column can't search.
                        'targets': [0, 1]
                    },
                    {
                        'className': 'control',
                        'data': 'tid',
                        'targets': 0,
                        'render': function () {
                            // make first column render nothing (for responsive expand/collapse button only).
                            // this is for working with responsive expand/collapse column and AJAX.
                            return '';
                        }
                    },
                    {
                        'className': 'column-checkbox',
                        'data': 'tid',
                        'targets': 1,
                        'render': function(data, type, row, meta) {
                            return '<input type="checkbox" name="tid[]" value="' + row.tid + '">';
                        }
                    },
                    {
                        'data': 'tid',
                        'targets': 2,
                        'visible': false
                    },
                    {
                        'data': 't_name',
                        'targets': 3,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                            let template = Handlebars.compile(source);
                            row.RdbCMSATagsIndexObject = RdbCMSATagsIndexObject;

                            let html = '<a class="rdba-listpage-edit" href="' + RdbCMSATagsIndexObject.editTagUrlBase + '/' + row.tid + '">' + RdbaCommon.escapeHtml(data) + '</a>'
                                + template(row);
                            return html;
                        }
                    },
                    {
                        'data': 't_description',
                        'orderable': false,
                        'targets': 4,
                        'render': function(data, type, row, meta) {
                            return RdbaCommon.truncateString(RdbaCommon.stripTags(row.t_description), 100);
                        }
                    },
                    {
                        'data': 'alias_url',
                        'targets': 5
                    }
                ],
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
                    json.RdbCMSATagsIndexObject = RdbCMSATagsIndexObject;
                    // add search controls.
                    thisClass.addCustomResultControls(json);
                    // add bulk actions controls.
                    thisClass.addActionsControls(json);
                    addedCustomResultControls = true;
                }

                if (RdbaCommon.isset(() => json.urls.addTagUrl)) {
                    // if there is 'add item URL'. it is possible that this URL will be changed due to filter type.
                    // modify the 'add' link.
                    let aElement = document.querySelector('.rdba-listpage-addnew');
                    aElement.setAttribute('href', json.urls.addTagUrl);
                }

                // add pagination.
                thisClass.addCustomResultControlsPagination(json);

                if (json && json.formResultMessage) {
                    RdbaCommon.displayAlertboxFixed(json.formResultMessage, json.formResultStatus);
                }

                if (json) {
                    if (typeof(json.csrfKeyPair) !== 'undefined') {
                        RdbCMSATagsIndexObject.csrfKeyPair = json.csrfKeyPair;
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
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        // activate data table.
        this.activateDataTable();
    }// init


    /**
     * Listen on bulk action form submit and open as ajax inside dialog.
     * 
     * @returns {undefined}
     */
    listenFormSubmit() {
        let thisClass = this;

        document.addEventListener('submit', function(event) {
            if (event.target && event.target.id === 'tags-list-form') {
                event.preventDefault();
                let thisForm = event.target;

                // validate selected item.
                let formValidated = false;
                let itemIdsArray = [];
                thisForm.querySelectorAll('input[type="checkbox"][name="tid[]"]:checked').forEach(function(item, index) {
                    itemIdsArray.push(item.value);
                });
                if (itemIdsArray.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbCMSATagsIndexObject.txtPleaseSelectAtLeastOne,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                let selectAction = thisForm.querySelector('#tags-list-actions');
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': RdbCMSATagsIndexObject.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    } else {
                        formValidated = true;
                    }
                }

                if (formValidated === true) {
                    // if form validated.
                    let bulkActionSelectbox = thisForm.querySelector('#tags-list-actions');
                    if (bulkActionSelectbox) {
                        if (bulkActionSelectbox.value === 'recount') {
                            // if bulk action is to re-count or update total items.
                            thisClass.listenFormSubmitBulkActions(itemIdsArray);
                        } else if (bulkActionSelectbox.value === 'delete') {
                            // if bulk action is to delete items.
                            thisClass.listenFormSubmitConfirmDelete(itemIdsArray, selectAction.value);
                        }
                    }
                }
            }
        });
    }// listenFormSubmit


    /**
     * Ajax bulk action other than delete.
     * 
     * @private This method was called from `listenFormSubmit()` method.
     * @param {type} tagIdsArray
     * @returns {undefined}
     */
    listenFormSubmitBulkActions(tagIdsArray) {
        let thisClass = this;
        let thisForm = document.querySelector(this.formIDSelector);
        let submitBtn = thisForm.querySelector('button[type="submit"]');

        let formData = new FormData(thisForm);
        formData.append(RdbCMSATagsIndexObject.csrfName, RdbCMSATagsIndexObject.csrfKeyPair[RdbCMSATagsIndexObject.csrfName]);
        formData.append(RdbCMSATagsIndexObject.csrfValue, RdbCMSATagsIndexObject.csrfKeyPair[RdbCMSATagsIndexObject.csrfValue]);

        // reset form result placeholder
        thisForm.querySelector('.form-result-placeholder').innerHTML = '';
        // add spinner icon
        thisForm.querySelector('.action-status-placeholder').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
        // lock submit button
        submitBtn.disabled = true;

        RdbaCommon.XHR({
            'url': RdbCMSATagsIndexObject.bulkActionsTagRESTUrlBase + '/' + tagIdsArray.join(','),
            'method': RdbCMSATagsIndexObject.bulkActionsTagRESTMethod,
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
                RdbCMSATagsIndexObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.reject(responseObject);
        })
        .then(function(responseObject) {
            // XHR success.
            let response = responseObject.response;

            // reload datatable.
            jQuery(thisClass.datatableIDSelector).DataTable().ajax.reload(null, false);

            if (typeof(response) !== 'undefined') {
                if (typeof(response.formResultMessage) !== 'undefined') {
                    RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                }
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbCMSATagsIndexObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.resolve(responseObject);
        })
        .finally(function() {
            // remove loading icon
            thisForm.querySelector('.loading-icon').remove();
            // unlock submit button
            submitBtn.disabled = false;
        });
    }// listenFormSubmitBulkActions


    /**
     * Ask for confirm delete.
     * 
     * @private This method was called from `listenFormSubmit()` method.
     * @param {array} tids
     * @param {string} action
     * @returns {Boolean}
     */
    listenFormSubmitConfirmDelete(tids, action) {
        if (!_.isArray(tids)) {
            console.error('The IDs are not array.');
            return false;
        }

        let thisClass = this;

        if (action === 'delete') {
            // if selected action is delete.
            let confirmValue = confirm(RdbCMSATagsIndexObject.txtConfirmDelete);
            let thisForm = document.querySelector(thisClass.formIDSelector);
            let submitBtn = thisForm.querySelector('button[type="submit"]');

            let formData = new FormData(thisForm);
            formData.append('tids', tids.join(','));
            formData.append(RdbCMSATagsIndexObject.csrfName, RdbCMSATagsIndexObject.csrfKeyPair[RdbCMSATagsIndexObject.csrfName]);
            formData.append(RdbCMSATagsIndexObject.csrfValue, RdbCMSATagsIndexObject.csrfKeyPair[RdbCMSATagsIndexObject.csrfValue]);

            if (confirmValue === true) {
                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.action-status-placeholder').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                submitBtn.disabled = true;

                RdbaCommon.XHR({
                    'url': RdbCMSATagsIndexObject.deleteTagRESTUrlBase + '/' + tids.join(','),
                    'method': RdbCMSATagsIndexObject.deleteTagRESTMethod,
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
                        RdbCMSATagsIndexObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    // reload datatable.
                    jQuery(thisClass.datatableIDSelector).DataTable().ajax.reload(null, false);

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbCMSATagsIndexObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.resolve(responseObject);
                })
                .finally(function() {
                    // remove loading icon
                    thisForm.querySelector('.loading-icon').remove();
                    // unlock submit button
                    submitBtn.disabled = false;
                });
            }
            // end action == delete
        }// endif action
    }// listenFormSubmitConfirmDelete


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
        document.getElementById('rdba-filter-search').value = '';

        // datatables have to call with jQuery.
        $(thisClass.datatableIDSelector).DataTable().order(thisClass.defaultSortOrder).search('').draw();// .order must match in columnDefs.

        return false;
    }// resetDataTable


}// RdbCMSATagsIndexController


document.addEventListener('DOMContentLoaded', function() {
    let rdbcmsaTagsIndexControllerClass = new RdbCMSATagsIndexController();
    let rdbaXhrDialog = new RdbaXhrDialog({
        'dialogIDSelector': '#tags-editing-dialog',
        'dialogNewInitEvent': 'tags.editing.newinit',
        'dialogReInitEvent': 'tags.editing.reinit',
        'xhrLinksSelector': '.rdba-listpage-addnew, .rdba-listpage-edit'
    });
    rdbcmsaTagsIndexControllerClass.setRdbaXhrDialogObject(rdbaXhrDialog);

    // initialize datatables.
    rdbcmsaTagsIndexControllerClass.init();

    // set of methods to work on click add, edit and open as dialog instead of new page. -----------------
    // links to be ajax.
    rdbaXhrDialog.listenAjaxLinks();
    // listen on closed dialog and maybe change URL.
    rdbaXhrDialog.listenDialogClose();
    // listen on popstate and controls dialog.
    rdbaXhrDialog.listenPopStateControlsDialog();
    // end set of methods to open page as dialog. --------------------------------------------------------------

    // listen form submit (bulk actions).
    rdbcmsaTagsIndexControllerClass.listenFormSubmit();
}, false);