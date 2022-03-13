/**
 * Manage contents categories.
 */


class RdbCMSACategoriesIndexController extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        super(options);

        this.formIDSelector = '#rdbcmsa-contents-categories-form';
        this.datatableIDSelector = '#contentsCategoriesTable';
        this.defaultSortOrder = [];
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
                    'url': RdbCMSACategoriesIndexObject.getCategoriesRESTUrl,
                    'method': RdbCMSACategoriesIndexObject.getCategoriesRESTMethod,
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
                        'orderable': false,
                        'targets': 2,
                        'visible': false
                    },
                    {
                        'data': 't_name',
                        'orderable': false,
                        'targets': 3,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                            let template = Handlebars.compile(source);
                            Handlebars.registerHelper('replace', function (find, replace, options) {
                                let string = options.fn(this);
                                return string.replace(find, replace);
                            });
                            row.RdbCMSACategoriesIndexObject = RdbCMSACategoriesIndexObject;

                            let categoryName = '';
                            let indentText = '&mdash; ';
                            if (row.t_level > 1) {
                                categoryName += indentText.repeat((row.t_level - 1));
                            }
                            categoryName += '<a class="rdba-listpage-edit" href="' + RdbCMSACategoriesIndexObject.editCategoryUrlBase + '/' + row.tid + '">' + RdbaCommon.escapeHtml(data) + '</a>';

                            let html = categoryName + template(row);
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
                        'orderable': false,
                        'targets': 5
                    },
                    {
                        'data': 't_status',
                        'orderable': false,
                        'targets': 6,
                        'render': function(data, type, row, meta) {
                            if (row.t_status == '1') {
                                return RdbCMSACategoriesIndexObject.txtPublished;
                            } else {
                                return RdbCMSACategoriesIndexObject.txtUnpublished;
                            }
                        }
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
                    json.RdbCMSACategoriesIndexObject = RdbCMSACategoriesIndexObject;
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
                        RdbCMSACategoriesIndexObject.csrfKeyPair = json.csrfKeyPair;
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
            if (event.target && event.target.id === 'rdbcmsa-contents-categories-form') {
                event.preventDefault();
                let thisForm = event.target;

                // validate selected item.
                let formValidated = false;
                let categoryIdsArray = [];
                thisForm.querySelectorAll('input[type="checkbox"][name="tid[]"]:checked').forEach(function(item, index) {
                    categoryIdsArray.push(item.value);
                });
                if (categoryIdsArray.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbCMSACategoriesIndexObject.txtPleaseSelectAtLeastOne,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                let selectAction = thisForm.querySelector('#rdbcmsa-contents-categories-actions');
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': RdbCMSACategoriesIndexObject.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    } else {
                        formValidated = true;
                    }
                }

                if (formValidated === true) {
                    let bulkActionSelectbox = thisForm.querySelector('#rdbcmsa-contents-categories-actions');
                    if (bulkActionSelectbox) {
                        if (bulkActionSelectbox.value === 'recount') {
                            // if bulk action is to re-count or update total items.
                            thisClass.listenFormSubmitBulkActions(categoryIdsArray);
                        } else if (bulkActionSelectbox.value === 'delete') {
                            // if bulk action is to delete items.
                            let ajaxUrl = RdbCMSACategoriesIndexObject.actionsCategoriesUrl + '?tids=' + categoryIdsArray.join(',') + '&action=' + selectAction.value;
                            thisClass.RdbaXhrDialog.ajaxOpenLinkInDialog(ajaxUrl);
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
     * @param {type} categoryIdsArray
     * @returns {undefined}
     */
    listenFormSubmitBulkActions(categoryIdsArray) {
        let thisClass = this;
        let thisForm = document.querySelector(this.formIDSelector);
        let submitBtn = thisForm.querySelector('button[type="submit"]');

        let formData = new FormData(thisForm);
        formData.append(RdbCMSACategoriesIndexObject.csrfName, RdbCMSACategoriesIndexObject.csrfKeyPair[RdbCMSACategoriesIndexObject.csrfName]);
        formData.append(RdbCMSACategoriesIndexObject.csrfValue, RdbCMSACategoriesIndexObject.csrfKeyPair[RdbCMSACategoriesIndexObject.csrfValue]);

        // reset form result placeholder
        thisForm.querySelector('.form-result-placeholder').innerHTML = '';
        // add spinner icon
        thisForm.querySelector('.action-status-placeholder').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
        // lock submit button
        submitBtn.disabled = true;

        RdbaCommon.XHR({
            'url': RdbCMSACategoriesIndexObject.bulkActionsCategoryRESTUrlBase + '/' + categoryIdsArray.join(','),
            'method': RdbCMSACategoriesIndexObject.bulkActionsCategoryRESTMethod,
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
                RdbCMSACategoriesIndexObject.csrfKeyPair = response.csrfKeyPair;
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
                RdbCMSACategoriesIndexObject.csrfKeyPair = response.csrfKeyPair;
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


}// RdbCMSACategoriesIndexController


document.addEventListener('DOMContentLoaded', function() {
    let rdbcmsaCategoriesIndexControllerClass = new RdbCMSACategoriesIndexController();
    let rdbaXhrDialog = new RdbaXhrDialog({
        'dialogIDSelector': '#rdbcmsa-contents-categories-dialog',
        'dialogNewInitEvent': 'rdbcmsa.contents-categories.editing.newinit',
        'dialogReInitEvent': 'rdbcmsa.contents-categories.editing.reinit',
        'xhrLinksSelector': '.rdba-listpage-addnew, .rdba-listpage-edit'
    });
    rdbcmsaCategoriesIndexControllerClass.setRdbaXhrDialogObject(rdbaXhrDialog);

    // initialize datatables.
    rdbcmsaCategoriesIndexControllerClass.init();

    // set of methods to work on click add, edit and open as dialog instead of new page. -----------------
    // links to be ajax.
    rdbaXhrDialog.listenAjaxLinks();
    // listen on closed dialog and maybe change URL.
    rdbaXhrDialog.listenDialogClose();
    // listen on popstate and controls dialog.
    rdbaXhrDialog.listenPopStateControlsDialog();
    // end set of methods to open page as dialog. --------------------------------------------------------------

    // listen form submit (bulk actions).
    rdbcmsaCategoriesIndexControllerClass.listenFormSubmit();
}, false);