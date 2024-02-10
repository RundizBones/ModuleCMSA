/**
 * URL Aliases management JS for its controller.
 */


class RdbCMSAToolsURLAliasesIndexController extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     * @returns {RdbaRolesController}
     */
    constructor(options) {
        super(options);

        this.formIDSelector = '#rdbcmsaToolsUrlAliases-list-form';
        this.datatableIDSelector = '#rdbcmsaToolsURLAliasesTable';
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

        $.when(uiXhrCommonData)// uiXhrCommonData is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
        .done(function() {
            let dataTable = $(thisClass.datatableIDSelector).DataTable({
                'ajax': {
                    'url': RdbCMSAToolsURLAliasesIndexObject.getAliasesRESTUrl,
                    'method': RdbCMSAToolsURLAliasesIndexObject.getAliasesRESTMethod,
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
                        'data': 'alias_id',
                        'targets': 0,
                        'render': function () {
                            // make first column render nothing (for responsive expand/collapse button only).
                            // this is for working with responsive expand/collapse column and AJAX.
                            return '';
                        }
                    },
                    {
                        'className': 'column-checkbox',
                        'data': 'alias_id',
                        'targets': 1,
                        'render': function(data, type, row, meta) {
                            return '<input type="checkbox" name="alias_id[]" value="' + row.alias_id + '">';
                        }
                    },
                    {
                        'data': 'alias_id',
                        'targets': 2,
                        'visible': false
                    },
                    {
                        'data': 'alias_url',
                        'targets': 3,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                            let template = Handlebars.compile(source);
                            row.RdbCMSAToolsURLAliasesIndexObject = RdbCMSAToolsURLAliasesIndexObject;
                            let html = '';
                            if (data !== null) {
                                html = '<a class="rdba-listpage-edit" href="' + RdbCMSAToolsURLAliasesIndexObject.editAliasUrlBase + '/' + row.alias_id + '">' + data + '</a>';
                            }
                            html += template(row);
                            return html;
                        }
                    },
                    {
                        'data': 'alias_content_id',
                        'targets': 4
                    },
                    {
                        'data': 'alias_content_type',
                        'targets': 5,
                        'render': function(data, type, row, meta) {
                            if (data === null && row.alias_redirect_to != '') {
                                return '<em class="text-color-info">' + RdbCMSAToolsURLAliasesIndexObject.txtRedirectionFunction + '</em>';
                            } else {
                                return data;
                            }
                        }
                    },
                    {
                        'data': 'alias_redirect_to',
                        'targets': 6
                    },
                    {
                        'data': 'alias_redirect_code',
                        'targets': 7
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
                    json.RdbCMSAToolsURLAliasesIndexObject = RdbCMSAToolsURLAliasesIndexObject;
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
                        RdbCMSAToolsURLAliasesIndexObject.csrfKeyPair = json.csrfKeyPair;
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
            if (event.target && event.target.id === 'rdbcmsaToolsUrlAliases-list-form') {
                event.preventDefault();
                let thisForm = event.target;

                // validate selected item.
                let formValidated = false;
                let itemIdsArray = [];
                thisForm.querySelectorAll('input[type="checkbox"][name="alias_id[]"]:checked').forEach(function(item, index) {
                    itemIdsArray.push(item.value);
                });
                if (itemIdsArray.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbCMSAToolsURLAliasesIndexObject.txtPleaseSelectAtLeastOne,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                let selectAction = thisForm.querySelector('#rdbcmsaToolsUrlAliases-list-actions');
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': RdbCMSAToolsURLAliasesIndexObject.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    } else {
                        formValidated = true;
                    }
                }

                if (formValidated === true) {
                    // if form validated.
                    thisClass.listenFormSubmitConfirmDelete(itemIdsArray, selectAction.value);
                }
            }
        });
    }// listenFormSubmit


    /**
     * Ask for confirm delete.
     * 
     * @private This method was called from `listenFormSubmit()` method.
     * @param {array} ids
     * @param {string} action
     * @returns {Boolean}
     */
    listenFormSubmitConfirmDelete(ids, action) {
        if (!_.isArray(ids)) {
            console.error('[rdbcmsa]: The IDs are not array.');
            return false;
        }

        let thisClass = this;

        if (action === 'delete') {
            // if selected action is delete.
            let confirmValue = confirm(RdbCMSAToolsURLAliasesIndexObject.txtConfirmDelete);
            let thisForm = document.querySelector(thisClass.formIDSelector);
            let submitBtn = thisForm.querySelector('button[type="submit"]');

            let formData = new FormData(thisForm);
            formData.append('ids', ids.join(','));
            formData.append(RdbCMSAToolsURLAliasesIndexObject.csrfName, RdbCMSAToolsURLAliasesIndexObject.csrfKeyPair[RdbCMSAToolsURLAliasesIndexObject.csrfName])
            formData.append(RdbCMSAToolsURLAliasesIndexObject.csrfValue, RdbCMSAToolsURLAliasesIndexObject.csrfKeyPair[RdbCMSAToolsURLAliasesIndexObject.csrfValue])

            if (confirmValue === true) {
                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.action-status-placeholder').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                submitBtn.disabled = true;

                RdbaCommon.XHR({
                    'url': RdbCMSAToolsURLAliasesIndexObject.deleteAliasRESTUrlBase + '/' + ids.join(','),
                    'method': RdbCMSAToolsURLAliasesIndexObject.deleteAliasRESTMethod,
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
                        RdbCMSAToolsURLAliasesIndexObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    // reload datatable.
                    jQuery(thisClass.datatableIDSelector).DataTable().ajax.reload(null, false);

                    RdbaCommon.displayAlertboxFixed(RdbCMSAToolsURLAliasesIndexObject.txtDeletedSuccess, 'success');

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbCMSAToolsURLAliasesIndexObject.csrfKeyPair = response.csrfKeyPair;
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


}// RdbCMSAToolsURLAliasesIndexController


document.addEventListener('DOMContentLoaded', function() {
    let rdbcmsaToolsURLAliasesIndexControllerClass = new RdbCMSAToolsURLAliasesIndexController();
    let rdbaXhrDialog = new RdbaXhrDialog({
        'dialogIDSelector': '#rdbcmsaToolsUrlAliases-editing-dialog',
        'dialogNewInitEvent': 'toolsurlaliasesdialog.editing.newinit',
        'dialogReInitEvent': 'toolsurlaliasesdialog.editing.reinit',
        'xhrLinksSelector': '.rdba-listpage-addnew, .rdba-listpage-edit'
    });
    rdbcmsaToolsURLAliasesIndexControllerClass.setRdbaXhrDialogObject(rdbaXhrDialog);

    // initialize datatables.
    rdbcmsaToolsURLAliasesIndexControllerClass.init();

    // set of methods to work on click add, edit and open as dialog instead of new page. -----------------
    // links to be ajax.
    rdbaXhrDialog.listenAjaxLinks();
    // listen on closed dialog and maybe change URL.
    rdbaXhrDialog.listenDialogClose();
    // listen on popstate and controls dialog.
    rdbaXhrDialog.listenPopStateControlsDialog();
    // end set of methods to open page as dialog. --------------------------------------------------------------

    // listen form submit (bulk actions).
    rdbcmsaToolsURLAliasesIndexControllerClass.listenFormSubmit();
}, false);