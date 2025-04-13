/**
 * Pages managenent page JS for its controller.
 */


class RdbCMSAPostsIndexController extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        super(options);

        this.formIDSelector = '#posts-list-form';
        this.datatableIDSelector = '#postsListItemsTable';
        this.defaultSortOrder = [[2, 'desc']];
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

        $.when(uiXhrCommonData)// uiXhrCommonData is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
        .done(function() {
            let dataTableOptions = {
                'ajax': {
                    'url': RdbCMSAPostsIndexObject.getPostsRESTUrl,
                    'method': RdbCMSAPostsIndexObject.getPostsRESTMethod,
                    'dataSrc': 'listItems',// change array key of data source. see https://datatables.net/examples/ajax/custom_data_property.html
                    'data': function(data) {
                        data['filter-post_status'] = $('#rdba-filter-post_status').val();
                        data['filter-tid'] = $('#rdba-filter-tid').val();
                        data['filter-user_id'] = $('#rdba-filter-user_id').val();
                        data['filter-tag-tid'] = $('#rdba-filter-tag-tid').val();
                    }
                },
                'columnDefs': [
                    {
                        'orderable': false,// make checkbox column not sortable.
                        'searchable': false,// make checkbox column can't search.
                        'targets': [0, 1]
                    },
                    {
                        'className': 'control',
                        'data': 'post_id',
                        'targets': 0,
                        'render': function () {
                            // make first column render nothing (for responsive expand/collapse button only).
                            // this is for working with responsive expand/collapse column and AJAX.
                            return '';
                        }
                    },
                    {
                        'className': 'column-checkbox',
                        'data': 'post_id',
                        'targets': 1,
                        'render': function(data, type, row, meta) {
                            return '<input type="checkbox" name="post_id[]" value="' + row.post_id + '">';
                        }
                    },
                    {
                        'data': 'post_id',
                        'targets': 2,
                        'visible': false
                    },
                    {
                        'data': 'post_name',
                        'targets': 3,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                            let template = Handlebars.compile(source);
                            Handlebars.registerHelper('replace', function (find, replace, options) {
                                let string = options.fn(this);
                                return string.replace(find, replace);
                            });
                            Handlebars.registerHelper('replaceBaseUrl', function (replace, options) {
                                const re = new RegExp('^(' + RdbaUIXhrCommonData.urls.baseUrlRaw + ')', 'i');
                                let string = options.fn(this);
                                return string.replace(re, RdbaUIXhrCommonData.urls.baseUrl + '/' + replace);
                            });
                            Handlebars.registerHelper('ifeq', function (a, b, options) {
                                if (a == b) { return options.fn(this); }
                                return options.inverse(this);
                            });
                            Handlebars.registerHelper('ifnoteq', function (a, b, options) {
                                if (a != b) { return options.fn(this); }
                                return options.inverse(this);
                            });

                            row.RdbCMSAPostsIndexObject = RdbCMSAPostsIndexObject;
                            row.RdbaUIXhrCommonData = RdbaUIXhrCommonData;

                            let html = '<a href="' + RdbCMSAPostsIndexObject.editPostUrlBase + '/' + row.post_id + '">' + data + '</a>';
                            if (row.post_status != '1') {
                                html += ' &mdash; <em>' + row.post_statusText + '</em>';
                            }
                            html += template(row);
                            return html;
                        }
                    },
                    {
                        'data': 'alias_url',
                        'targets': 4
                    },
                    {
                        'data': 'user_id',
                        'orderable': false,
                        'targets': 5,
                        'render': function(data, type, row, meta) {
                            return '<a href="' + RdbCMSAPostsIndexObject.editUserPageUrlBase + '/' + data + '" title="' + RdbaCommon.escapeHtml(row.user_display_name) + '">' + RdbaCommon.truncateString(row.user_display_name, 20) + '</a>';
                        }
                    },
                    {
                        'data': 'post_add',
                        'targets': 6,
                        'render': function(data, type, row, meta) {
                            if (row.post_add_gmt) {
                                let dateString = '';
                                dateString += '<small>' + RdbCMSAPostsIndexObject.txtDateAdd.replace('%s', moment(row.post_add_gmt + 'Z').tz(siteTimezone).format('YYYY-M-D HH:mm:ss Z')) + '</small><br>';
                                dateString += '<small>' + RdbCMSAPostsIndexObject.txtDateUpdate.replace('%s', moment(row.post_update_gmt + 'Z').tz(siteTimezone).format('YYYY-M-D HH:mm:ss Z')) + '</small><br>';
                                if (row.post_publish_date_gmt) {
                                    dateString += '<small>' + RdbCMSAPostsIndexObject.txtDatePublish.replace('%s', moment(row.post_publish_date_gmt + 'Z').tz(siteTimezone).format('YYYY-M-D HH:mm:ss Z')) + '</small><br>';
                                }
                                return dateString;
                            } else {
                                return '';
                            }
                        }
                    }
                ],
                'order': thisClass.defaultSortOrder,
                'serverSide': true,
            };
            dataTableOptions = thisClass.applyToDefaultDataTableOptions(dataTableOptions);
            let dataTable = new DataTable(thisClass.datatableIDSelector, dataTableOptions);

            // datatables events
            dataTable.on('xhr.dt', function(e, settings, json, xhr) {
                if (addedCustomResultControls === false) {
                    // if it was not added custom result controls yet.
                    // set additional data.
                    json.RdbCMSAPostsIndexObject = RdbCMSAPostsIndexObject;
                    // add search controls.
                    thisClass.addCustomResultControls(json);
                    // add bulk actions controls.
                    thisClass.addActionsControls(json);
                    addedCustomResultControls = true;

                    // ajax get filters value and set the results (in search controls).
                    thisClass.ajaxGetFiltersValue();

                    // listen on keyup and make tag auto complete.
                    thisClass.listenTagAutocomplete();
                    // listen on change auto complete and make the custom result.
                    thisClass.listenOnChangeAutocomplete();
                }

                if (RdbaCommon.isset(() => json.urls.addPostUrl)) {
                    // if there is 'add item URL'. it is possible that this URL will be changed due to filter type.
                    // modify the 'add' link.
                    let aElement = document.querySelector('.rdba-listpage-addnew');
                    aElement.setAttribute('href', json.urls.addPostUrl);
                }

                // add pagination.
                thisClass.addCustomResultControlsPagination(json);

                if (json && json.formResultMessage) {
                    RdbaCommon.displayAlertboxFixed(json.formResultMessage, json.formResultStatus);
                }

                if (json) {
                    if (typeof(json.csrfKeyPair) !== 'undefined') {
                        RdbCMSAPostsIndexObject.csrfKeyPair = json.csrfKeyPair;
                    }
                }
            })// datatables on xhr complete.
            .on('draw', function() {
                // add listening events.
                thisClass.addCustomResultControlsEvents(dataTable);
                // modify bulk actions option.
                thisClass.datatableModifyBulkActions();
            })// datatables on draw complete.
            ;
        });// uiXhrCommonData.done()
    }// activateDataTable


    /**
     * Ajax get new filters value.
     * 
     * @private This method was called from `activateDataTable()`, `reloadDataTable()` methods.
     * @returns {undefined}
     */
    ajaxGetFiltersValue() {
        let thisClass = this;
        let thisForm = document.querySelector(thisClass.formIDSelector);

        RdbaCommon.XHR({
            'url': RdbCMSAPostsIndexObject.getPostFiltersRESTUrl,
            'method': RdbCMSAPostsIndexObject.getPostFiltersRESTMethod,
            'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
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
                RdbCMSAPostsIndexObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.reject(responseObject);
        })
        .then(function(responseObject) {
            // XHR success.
            let response = responseObject.response;

            if (typeof(response) !== 'undefined') {
                if (typeof(response.allPostStatuses) !== 'undefined') {
                    let optionString = '';
                    response.allPostStatuses.forEach(function(item, index) {
                        optionString += '<option value="' + item.value + '">' + RdbaCommon.escapeHtml(item.text);
                        if (item.posts && item.posts > 0) {
                            optionString += ' (' + item.posts + ')';
                        }
                        optionString += '</option>';
                    });
                    thisForm.querySelector('#rdba-filter-post_status').innerHTML = optionString;
                }

                if (typeof(response.allAuthors) !== 'undefined') {
                    let optionString = '';
                    response.allAuthors.forEach(function(item, index) {
                        optionString += '<option value="' + item.value + '">';
                        optionString += RdbaCommon.escapeHtml(item.text);
                        optionString += '</option>';
                    });
                    thisForm.querySelector('#rdba-filter-user_id').innerHTML = optionString;
                }
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbCMSAPostsIndexObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.resolve(responseObject);
        });
    }// ajaxGetFiltersValue


    /**
     * Modify bulk actions option by show or hide some action depend on post status the is currently filtered.
     * 
     * @private This method was called from `activateDataTable()` method.
     * @returns {undefined}
     */
    datatableModifyBulkActions() {
        let postStatus = document.getElementById('rdba-filter-post_status');
        let bulkActions = document.getElementById('posts-list-actions');

        if (bulkActions && postStatus) {
            let bulkActionsTrashOption = bulkActions.querySelector('option[value="trash"]');
            let bulkActionsOtherOptions = bulkActions.querySelectorAll('option:not([value="trash"]):not([value=""])');
            if (postStatus.value == '5') {
                // if post status is viewing trash bin.
                bulkActionsTrashOption.disabled = true;
                bulkActionsTrashOption.classList.add('rd-hidden');

                bulkActionsOtherOptions.forEach(function(item, index) {
                    item.disabled = false;
                    item.classList.remove('rd-hidden');
                });
            } else {
                // if post status is viewing something else.
                bulkActionsTrashOption.disabled = false;
                bulkActionsTrashOption.classList.remove('rd-hidden');

                bulkActionsOtherOptions.forEach(function(item, index) {
                    item.disabled = true;
                    item.classList.add('rd-hidden');
                });
            }

            // restore bulk action to default
            bulkActions.value = '';
        }
    }// datatableModifyBulkActions


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
            if (event.target && event.target.id === 'posts-list-form') {
                event.preventDefault();
                let thisForm = event.target;

                // validate selected item.
                let formValidated = false;
                let itemIdsArray = [];
                thisForm.querySelectorAll('input[type="checkbox"][name="post_id[]"]:checked').forEach(function(item, index) {
                    itemIdsArray.push(item.value);
                });
                if (itemIdsArray.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbCMSAPostsIndexObject.txtPleaseSelectAtLeastOne,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                let selectAction = thisForm.querySelector('#posts-list-actions');
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': RdbCMSAPostsIndexObject.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    } else {
                        formValidated = true;
                    }
                }

                if (formValidated === true) {
                    // if form validated.
                    if (selectAction.value === 'delete_permanently') {
                        // if action is permanently delete.
                        // ajax permanently delete post here.
                        thisClass.listenFormSubmitAjaxDelete(itemIdsArray);
                    } else {
                        // if other actions.
                        thisClass.listenFormSubmitAjaxBulkActions(itemIdsArray);
                    }
                }
            }
        });
    }// listenFormSubmit


    /**
     * Ajax bulk actions that is not permanently delete.
     * 
     * @private This method was called from `listenFormSubmit()` method.
     * @param {array} post_ids 
     * @returns {undefined}
     */
    listenFormSubmitAjaxBulkActions(post_ids) {
        let thisClass = this;
        let thisForm = document.querySelector(this.formIDSelector);
        let submitBtn = thisForm.querySelector('button[type="submit"]');

        let formData = new FormData(thisForm);
        formData.append(RdbCMSAPostsIndexObject.csrfName, RdbCMSAPostsIndexObject.csrfKeyPair[RdbCMSAPostsIndexObject.csrfName]);
        formData.append(RdbCMSAPostsIndexObject.csrfValue, RdbCMSAPostsIndexObject.csrfKeyPair[RdbCMSAPostsIndexObject.csrfValue]);

        // reset form result placeholder
        thisForm.querySelector('.form-result-placeholder').innerHTML = '';
        // add spinner icon
        thisForm.querySelector('.action-status-placeholder').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
        // lock submit button
        submitBtn.disabled = true;

        RdbaCommon.XHR({
            'url': RdbCMSAPostsIndexObject.postBulkActionsRESTUrlBase + '/' + post_ids.join(','),
            'method': RdbCMSAPostsIndexObject.postBulkActionsRESTMethod,
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
                RdbCMSAPostsIndexObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.reject(responseObject);
        })
        .then(function(responseObject) {
            // XHR success.
            let response = responseObject.response;

            // reload datatable.
            thisClass.reloadDataTable();

            if (typeof(response) !== 'undefined') {
                if (typeof(response.formResultMessage) !== 'undefined') {
                    RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                }
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbCMSAPostsIndexObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.resolve(responseObject);
        })
        .finally(function() {
            // remove loading icon
            thisForm.querySelector('.loading-icon').remove();
            // unlock submit button
            submitBtn.disabled = false;
        });
    }// listenFormSubmitAjaxBulkActions


    /**
     * Ajax permanently delete posts.
     * 
     * @private This method was called from `listenFormSubmit()` method.
     * @param {array} post_ids 
     * @returns {undefined}
     */
    listenFormSubmitAjaxDelete(post_ids) {
        if (!_.isArray(post_ids)) {
            console.error('[rdbcmsa]: The IDs are not array.');
            return false;
        }

        let thisClass = this;
        let thisForm = document.querySelector(this.formIDSelector);
        let submitBtn = thisForm.querySelector('button[type="submit"]');
        let confirmValue = confirm(RdbCMSAPostsIndexObject.txtConfirmDelete);

        let formData = new FormData(thisForm);
        formData.append(RdbCMSAPostsIndexObject.csrfName, RdbCMSAPostsIndexObject.csrfKeyPair[RdbCMSAPostsIndexObject.csrfName]);
        formData.append(RdbCMSAPostsIndexObject.csrfValue, RdbCMSAPostsIndexObject.csrfKeyPair[RdbCMSAPostsIndexObject.csrfValue]);

        if (confirmValue === true) {
            // reset form result placeholder
            thisForm.querySelector('.form-result-placeholder').innerHTML = '';
            // add spinner icon
            thisForm.querySelector('.action-status-placeholder').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
            // lock submit button
            submitBtn.disabled = true;

            RdbaCommon.XHR({
                'url': RdbCMSAPostsIndexObject.postBulkActionDeleteRESTUrlBase + '/' + post_ids.join(','),
                'method': RdbCMSAPostsIndexObject.postBulkActionDeleteRESTMethod,
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
                    RdbCMSAPostsIndexObject.csrfKeyPair = response.csrfKeyPair;
                }

                return Promise.reject(responseObject);
            })
            .then(function(responseObject) {
                // XHR success.
                let response = responseObject.response;

                // reload datatable.
                thisClass.reloadDataTable();

                if (typeof(response) !== 'undefined') {
                    RdbaCommon.displayAlertboxFixed(RdbCMSAPostsIndexObject.txtDeletedSuccessfully, 'success');
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbCMSAPostsIndexObject.csrfKeyPair = response.csrfKeyPair;
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
    }// listenFormSubmitAjaxDelete


    /**
     * Listen on change (on select) auto complete value of find tag field and make the custom result from datalist.
     * 
     * @private This method was called from `activeDataTable()` method.
     * @returns {undefined}
     */
    listenOnChangeAutocomplete() {
        document.addEventListener('input', function(event) {
            if (
                RdbaCommon.isset(() => event.target.id) &&
                event.target.id === 'prog_find_tag'
            ) {
                event.preventDefault();
                let thisInput = event.target;
                let selectedValue = event.target.value;// <option value="xxx">
                let filterInput = document.getElementById('rdba-filter-tag-tid');
                let selectedDatalist = document.querySelector('#prog_result_tags option[value="' + selectedValue + '"]');
                if (selectedDatalist) {
                    thisInput.value = selectedValue;
                    filterInput.value = selectedDatalist.dataset.value;
                }
            }
        });
    }// listenOnChangeAutocomplete


    /**
     * Listen on keyup and make tag auto complete.
     * 
     * @private This method was called from `activeDataTable()` method.
     * @returns {undefined}
     */
    listenTagAutocomplete() {
        let thisClass = this;

        document.addEventListener('keyup', function(event) {
            if (
                RdbaCommon.isset(() => event.target.id) &&
                event.target.id === 'prog_find_tag' &&
                event.key
            ) {
                event.preventDefault();
                let thisInput = event.target;
                let filterInput = document.getElementById('rdba-filter-tag-tid');
                let datalist = document.getElementById('prog_result_tags');
                filterInput.value = '';
                datalist.innerHTML = '';

                RdbaCommon.XHR({
                    'url': RdbCMSAPostsIndexObject.getTagsRESTUrl + '?search[value]=' + encodeURI(thisInput.value),
                    'method': RdbCMSAPostsIndexObject.getTagsRESTMethod,
                    'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
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
                        RdbCMSAPostsIndexObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    if (RdbaCommon.isset(() => response.listItems)) {
                        response.listItems.forEach(function(item, index) {
                            let option = '<option data-t_name="' + item.t_name + '" data-value="' + item.tid + '" value="' + item.t_name + '"></option>';
                            datalist.insertAdjacentHTML('beforeend', option);
                        });
                    }
                });
            }
        });

        document.addEventListener('keydown', function(event) {
            if (
                RdbaCommon.isset(() => event.target.id) &&
                event.target.id === 'prog_find_tag' &&
                RdbaCommon.isset(() => event.key) &&
                event.key.toLowerCase() == 'enter'
            ) {
                event.preventDefault();
                console.log('[rdbcmsa]: canceled enter key.');
            }
        });
    }// listenTagAutocomplete


    /**
     * Reload data table and filters.
     * 
     * @returns {undefined}
     */
    reloadDataTable() {
        let thisClass = this;

        new DataTable(thisClass.datatableIDSelector).ajax.reload(null, false);

        thisClass.ajaxGetFiltersValue();
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
        document.getElementById('rdba-filter-post_status').value = '';
        document.getElementById('rdba-filter-user_id').value = '';
        document.getElementById('rdba-filter-search').value = '';

        // datatables have to call with jQuery.
        new DataTable(thisClass.datatableIDSelector).order(thisClass.defaultSortOrder).search('').draw();// .order must match in columnDefs.

        return false;
    }// resetDataTable


}// RdbCMSAPostsIndexController


document.addEventListener('DOMContentLoaded', function() {
    let rdbcmsaPostsIndexControllerClass = new RdbCMSAPostsIndexController();

    // initialize datatables.
    rdbcmsaPostsIndexControllerClass.init();

    // listen form submit (bulk actions).
    rdbcmsaPostsIndexControllerClass.listenFormSubmit();
}, false);