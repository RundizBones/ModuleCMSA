/**
 * Files management section in files management page.
 */


class RdbCMSAFilesIndexControllerFiles extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        super(options);

        this.formIDSelector = '#rdbcmsa-files-list-form';
        this.datatableIDSelector = '#filesListItemsTable';
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

        if (RdbCMSAFilesCommonObject.debug === true) {
            console.log('[rdbcmsa]: Loading files.');
        }

        $.when(uiXhrCommonData)// uiXhrCommonData is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
        .done(function() {
            let dataTable = $(thisClass.datatableIDSelector).DataTable({
                'ajax': {
                    'url': RdbCMSAFilesCommonObject.getFilesRESTUrl,
                    'method': RdbCMSAFilesCommonObject.getFilesRESTMethod,
                    'dataSrc': 'listItems',// change array key of data source. see https://datatables.net/examples/ajax/custom_data_property.html
                    'data': function(data) {
                        data['filter-file_folder'] = $('#rdbcmsa-files-filter-folder').val();
                    }
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
                        'data': 'file_id',
                        'targets': 0,
                        'render': function () {
                            // make first column render nothing (for responsive expand/collapse button only).
                            // this is for working with responsive expand/collapse column and AJAX.
                            return '';
                        }
                    },
                    {
                        'className': 'column-checkbox',
                        'data': 'file_id',
                        'targets': 1,
                        'render': function(data, type, row, meta) {
                            return '<input type="checkbox" name="file_id[]" value="' + row.file_id + '">';
                        }
                    },
                    {
                        'data': 'file_id',
                        'targets': 2,
                        'visible': false
                    },
                    {
                        'data': 'file_original_name',// use original name for user remember and easy on sorting.
                        'targets': 3,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                            let template = Handlebars.compile(source);
                            // @link https://stackoverflow.com/questions/42245693/handlebars-js-replacing-portion-of-string replace original source code.
                            Handlebars.registerHelper('replace', function (find, replace, options) {
                                let string = options.fn(this);
                                return string.replace(find, replace);
                            });
                            row.RdbCMSAFilesCommonObject = RdbCMSAFilesCommonObject;

                            let publicUrlWithFolderPrefix;
                            if (row.file_visibility === '1') {
                                // if in public/rdbadmin-public folder.
                                publicUrlWithFolderPrefix = RdbCMSAFilesCommonObject.rootPublicUrl + '/' + RdbCMSAFilesCommonObject.rootPublicFolderName;
                                if (!_.isEmpty(row.file_folder)) {
                                    publicUrlWithFolderPrefix += '/' + row.file_folder;
                                }
                                row.publicUrlWithFolderPrefix = publicUrlWithFolderPrefix;
                            }

                            let html = '';
                            if (row.file_visibility === '1' && RdbCMSAFilesCommonObject.imageExtensions.includes(row.file_ext.toLowerCase())) {
                                let thumbnailImage;
                                if (RdbaCommon.isset(() => row.thumbnails)) {
                                    for (const [key, value] of Object.entries(row.thumbnails)) {
                                        thumbnailImage = row.thumbnails[key];
                                        break;
                                    }
                                }
                                if (!thumbnailImage) {
                                    thumbnailImage = publicUrlWithFolderPrefix + '/' + row.file_name;
                                }
                                html += '<a class="rdbcmsa-files-image-thumbnail-link" href="' + publicUrlWithFolderPrefix + '/' + row.file_name + '" target="realImageFile">'
                                + '<img class="fluid rdbcmsa-files-image-thumbnail" src="' + thumbnailImage + '" alt="">'
                                + '</a>';
                            } else {
                                if (row.file_visibility === '1') {
                                    html += '<a class="rdbcmsa-files-image-thumbnail-link" href="' + publicUrlWithFolderPrefix + '/' + row.file_name + '" target="realImageFile">';
                                }
                                let iconClass = 'fa-regular fa-file';
                                if (row.file_mime_type.toLowerCase().includes('video/')) {
                                    iconClass = 'fa-regular fa-file-video';
                                } else if (row.file_mime_type.toLowerCase().includes('audio/')) {
                                    iconClass = 'fa-regular fa-file-audio';
                                }
                                html += '<i class="' + iconClass + ' fa-6x"></i>';
                                if (row.file_visibility === '1') {
                                    html += '</a>';
                                }
                            }
                            html += '<a class="rdba-listpage-edit" href="' + RdbCMSAFilesCommonObject.editFileUrlBase + '/' + row.file_id + '">'
                                + RdbaCommon.escapeHtml(data) 
                                + '</a>'
                                + template(row);
                            return html;
                        }
                    },
                    {
                        'data': 'user_id',
                        'orderable': false,
                        'targets': 4,
                        'render': function(data, type, row, meta) {
                            if (row.user_display_name) {
                                return '<a href="' + RdbCMSAFilesCommonObject.editUserPageUrlBase + '/' + data + '" title="' + RdbaCommon.escapeHtml(row.user_display_name) + '">' + RdbaCommon.truncateString(row.user_display_name, 20) + '</a>';
                            }
                            return data;
                        }
                    },
                    {
                        'data': 'file_size',
                        'targets': 5,
                        'render': function(data, type, row, meta) {
                            return RdbaCommon.humanFileSize(data, true);
                        }
                    },
                    {
                        'data': 'file_status',
                        'targets': 6,
                        'render': function(data, type, row, meta) {
                            if (row.file_status == '1') {
                                return RdbCMSAFilesCommonObject.txtPublished;
                            } else {
                                return RdbCMSAFilesCommonObject.txtUnpublished;
                            }
                        }
                    },
                    {
                        'data': 'file_add',
                        'targets': 7,
                        'render': function(data, type, row, meta) {
                            if (row.file_add_gmt) {
                                let dateString = '';
                                dateString += '<small>' + RdbCMSAFilesCommonObject.txtDateAdd.replace('%s', moment(row.file_add_gmt + 'Z').tz(siteTimezone).format('YYYY-M-D HH:mm:ss Z')) + '</small><br>';
                                dateString += '<small>' + RdbCMSAFilesCommonObject.txtDateUpdate.replace('%s', moment(row.file_update_gmt + 'Z').tz(siteTimezone).format('YYYY-M-D HH:mm:ss Z')) + '</small><br>';
                                return dateString;
                            } else {
                                return '';
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
                if (RdbCMSAFilesCommonObject.debug === true) {
                    console.log('[rdbcmsa]: Responded from list files.');
                }

                if (addedCustomResultControls === false) {
                    // if it was not added custom result controls yet.
                    // set additional data.
                    json.RdbCMSAFilesCommonObject = RdbCMSAFilesCommonObject;
                    // add search controls.
                    thisClass.addCustomResultControls(json);
                    // add bulk actions controls.
                    thisClass.addActionsControls(json);
                    addedCustomResultControls = true;
                }

                if (json && json.addTagUrl) {
                    // if there is 'add item URL'. it is possible that this URL will be changed due to filter type.
                    // modify the 'add' link.
                    let aElement = document.querySelector('.rdba-listpage-addnew');
                    aElement.setAttribute('href', json.addTagUrl);
                }

                // add pagination.
                thisClass.addCustomResultControlsPagination(json);

                if (json && json.formResultMessage) {
                    RdbaCommon.displayAlertboxFixed(json.formResultMessage, json.formResultStatus);
                }

                if (json) {
                    if (typeof(json.csrfKeyPair) !== 'undefined') {
                        RdbCMSAFilesCommonObject.csrfKeyPair = json.csrfKeyPair;
                    }
                    if (json.t_type) {
                        RdbCMSAFilesCommonObject.t_type = json.t_type;
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

        // listen file upload
        this.listenFileUpload();
    }// init


    /**
     * Listen select file or drop file and start upload.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenFileUpload() {
        let thisClass = this;
        let dropzoneId = 'rdbcmsa-files-dropzone';
        let inputFileId = 'files_inputfiles';
        let inputFileElement = document.querySelector('#' + inputFileId);

        // prevent drag & drop image file outside drop zone. --------------------------------------------------
        window.addEventListener('dragenter', function (e) {
            let thisTarget = e.target;
            let closestElement = null;
            if (typeof(thisTarget.tagName) !== 'undefined') {
                closestElement = thisTarget.closest('#' + dropzoneId);
            }
            if (closestElement === null && thisTarget.parentElement) {
                closestElement = thisTarget.parentElement.closest('#' + dropzoneId);
            }
            if (closestElement === '' || closestElement === null) {
                e.preventDefault();
                e.dataTransfer.effectAllowed = 'none';
                e.dataTransfer.dropEffect = 'none';
            } else {
                e.preventDefault();// prevent redirect page to show dropped image.
            }
        }, false);
        window.addEventListener('dragover', function (e) {
            let thisTarget = e.target;
            let closestElement = null;
            if (typeof(thisTarget.tagName) !== 'undefined') {
                closestElement = thisTarget.closest('#' + dropzoneId);
            }
            if (closestElement === null && thisTarget.parentElement) {
                closestElement = thisTarget.parentElement.closest('#' + dropzoneId);
            }
            if (closestElement === '' || closestElement === null) {
                e.preventDefault();
                e.dataTransfer.effectAllowed = 'none';
                e.dataTransfer.dropEffect = 'none';
            } else {
                e.preventDefault();// prevent redirect page to show dropped image.
            }
        });
        // end prevent drag & drop image file outside drop zone. ---------------------------------------------

        window.addEventListener('drop', function(event) {
            let thisTarget = event.target;
            let closestElement = null;
            if (typeof(thisTarget.tagName) !== 'undefined') {
                closestElement = thisTarget.closest('#' + dropzoneId);
            }
            if (closestElement === null && thisTarget.parentElement) {
                closestElement = thisTarget.parentElement.closest('#' + dropzoneId);
            }
            if (closestElement !== '' && closestElement !== null) {
                // if dropped in drop zone or input file.
                event.preventDefault();
                inputFileElement = document.querySelector('#' + inputFileId);// force get new data.
                inputFileElement.files = event.dataTransfer.files;
                //console.log('[rdbcmsa]: success set files to input file.', inputFileElement);
                inputFileElement.dispatchEvent(new Event('change', { 'bubbles': true }));
            } else {
                // if not dropped in drop zone and input file.
                event.preventDefault();
                //console.log('[rdbcmsa]: not in drop zone.');
                event.dataTransfer.effectAllowed = 'none';
                event.dataTransfer.dropEffect = 'none';
            }
        });

        if (inputFileElement) {
            let uploadStatusPlaceholder = document.getElementById('rdbcmsa-files-upload-status-placeholder');

            document.addEventListener('rdta.custominputfile.change', function(event) {
                event.preventDefault();

                inputFileElement = event.target;// force get new data.
                let selectedFolder = document.getElementById('rdbcmsa-files-filter-folder');

                // add loading icon.
                uploadStatusPlaceholder.innerHTML = '&nbsp;<i class="fa-solid fa-spinner fa-pulse loading-icon"></i> ' + RdbCMSAFilesCommonObject.txtUploading;

                let formData = new FormData();
                formData.append(RdbCMSAFilesCommonObject.csrfName, RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfName]);
                formData.append(RdbCMSAFilesCommonObject.csrfValue, RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfValue]);
                // append multiple files.
                // @link https://stackoverflow.com/a/14908250/128761 Original source code.
                let ins = inputFileElement.files.length;
                for (let x = 0; x < ins; x++) {
                    formData.append('files_inputfiles[]', inputFileElement.files[x]);
                }
                formData.append('filter-file_folder', (selectedFolder ? selectedFolder.value : ''));

                RdbaCommon.XHR({
                    'url': RdbCMSAFilesCommonObject.addFileRESTUrl,
                    'method': RdbCMSAFilesCommonObject.addFileRESTMethod,
                    //'contentType': 'multipart/form-data',// do not set `contentType` because it is already set in `formData`.
                    'data': formData,
                    'dataType': 'json',
                })
                .catch(function(responseObject) {
                    // XHR failed.
                    let response = responseObject.response;

                    if (response && response.formResultMessage) {
                        RDTAAlertDialog.alert({
                            'type': 'danger',
                            'text': response.formResultMessage
                        });
                    } else {
                        if (RdbaCommon.isset(() => responseObject.status) && responseObject.status === 500) {
                            RDTAAlertDialog.alert({
                                'type': 'danger',
                                'text': 'Internal Server Error'
                            });
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultStatus) !== 'undefined' && response.formResultStatus === 'warning') {
                            RDTAAlertDialog.alert({
                                'type': response.formResultStatus,
                                'text': response.formResultMessage
                            });
                        } else {
                            if (typeof(response.formResultMessage) !== 'undefined') {
                                RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                            }
                        }

                        if (typeof(response.uploadResult) !== 'undefined' && response.uploadResult === true) {
                            // if there is at least one file uploaded successfully.
                            // reset input file.
                            inputFileElement.value = '';
                            // reload files data table.
                            jQuery(thisClass.datatableIDSelector).DataTable().ajax.reload(null, false);
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.resolve(responseObject);
                })
                .finally(function() {
                    // remove loading icon and upload status text.
                    uploadStatusPlaceholder.innerHTML = '';
                });
            });
        }
    }// listenFileUpload


    /**
     * Listen on bulk action form submit and open as ajax inside dialog.
     * 
     * @returns {undefined}
     */
    listenFormSubmit() {
        let thisClass = this;

        document.addEventListener('submit', function(event) {
            if (event.target && '#' + event.target.id === thisClass.formIDSelector) {
                event.preventDefault();
                let thisForm = event.target;

                // validate selected item.
                let formValidated = false;
                let fileIdsArray = [];
                thisForm.querySelectorAll('input[type="checkbox"][name="file_id[]"]:checked').forEach(function(item, index) {
                    fileIdsArray.push(item.value);
                });
                if (fileIdsArray.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbCMSAFilesCommonObject.txtPleaseSelectAtLeastOne,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                // validate selected action.
                let selectAction = thisForm.querySelector('#rdbcmsa-files-list-actions');
                if (formValidated === true) {
                    if (selectAction && selectAction.value === '') {
                        RDTAAlertDialog.alert({
                            'text': RdbCMSAFilesCommonObject.txtPleaseSelectAction,
                            'type': 'error'
                        });
                        formValidated = false;
                    } else {
                        formValidated = true;
                    }
                }

                if (formValidated === true) {
                    if (selectAction) {
                        // @link https://stackoverflow.com/a/40195757/128761 Original source code of `indexOf()`.
                        let updateActionsArray = ['updatemeta', 'updatethumbnails', 'setwatermark', 'removewatermark'],
                            indexOfActions = (arr, q) => arr.findIndex(item => q.toLowerCase() === item.toLowerCase());

                        if (indexOfActions(updateActionsArray, selectAction.value) >= 0) {
                            thisClass.listenFormUpdateActions(selectAction.value, fileIdsArray);
                        } else if (selectAction.value === 'move' || selectAction.value === 'delete') {
                            // if bulk action is to delete items.
                            let ajaxUrl = RdbCMSAFilesCommonObject.actionsFilesUrl + '?file_ids=' + fileIdsArray.join(',') + '&action=' + selectAction.value;
                            thisClass.RdbaXhrDialog.ajaxOpenLinkInDialog(ajaxUrl);
                        }
                    }
                }
            }// endif; event.target
        });
    }// listenFormSubmit


    /**
     * Update actions.
     * 
     * @private This method was called from `listenFormSubmit()` method.
     * @param {string} selectActionValue
     * @param {array} fileIdsArray
     * @returns {undefined}
     */
    listenFormUpdateActions(selectActionValue, fileIdsArray) {
        let thisClass = this;
        let thisForm = document.querySelector(this.formIDSelector);
        let submitBtn = thisForm.querySelector('button[type="submit"]');

        let formData = new FormData();
        formData.append(RdbCMSAFilesCommonObject.csrfName, RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfName]);
        formData.append(RdbCMSAFilesCommonObject.csrfValue, RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfValue]);
        formData.append('action', selectActionValue);

        // reset form result placeholder
        thisForm.querySelector('.form-result-placeholder').innerHTML = '';
        // add spinner icon
        thisForm.querySelector('.action-status-placeholder').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
        // lock submit button
        submitBtn.disabled = true;

        RdbaCommon.XHR({
            'url': RdbCMSAFilesCommonObject.updateFileDataRESTUrl.replace('%file_ids%', fileIdsArray.join(',')).replace('%action%', selectActionValue),
            'method': RdbCMSAFilesCommonObject.updateFileDataRESTMethod,
            'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
            'data': new URLSearchParams(_.toArray(formData)).toString(),
            'dataType': 'json'
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
                RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
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
                RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.resolve(responseObject);
        })
        .finally(function() {
            // remove loading icon
            thisForm.querySelector('.loading-icon').remove();
            // unlock submit button
            submitBtn.disabled = false;
        });
    }// listenFormUpdateActions


    /**
     * Reload data table and filters.
     * 
     * @private This method was called from `listenFormUpdateActions()` method.
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
        document.getElementById('rdba-filter-search').value = '';

        // datatables have to call with jQuery.
        $(thisClass.datatableIDSelector).DataTable().order(thisClass.defaultSortOrder).search('').draw();// .order must match in columnDefs.

        return false;
    }// resetDataTable


}// RdbCMSAFilesIndexControllerFiles


document.addEventListener('DOMContentLoaded', function() {
    let filesFilesIndexController = new RdbCMSAFilesIndexControllerFiles();
    let rdbaXhrDialog = new RdbaXhrDialog({
        'dialogIDSelector': '#rdbcmsa-files-editing-dialog',
        'dialogNewInitEvent': 'files.editing.newinit',
        'dialogReInitEvent': 'files.editing.reinit',
        'xhrLinksSelector': '.rdba-listpage-addnew, .rdba-listpage-edit'
    });
    filesFilesIndexController.setRdbaXhrDialogObject(rdbaXhrDialog);

    // init the class.
    filesFilesIndexController.init();

    // set of methods to work on click add, edit and open as dialog instead of new page. -----------------
    // links to be ajax.
    rdbaXhrDialog.listenAjaxLinks();
    // listen on closed dialog and maybe change URL.
    rdbaXhrDialog.listenDialogClose();
    // listen on popstate and controls dialog.
    rdbaXhrDialog.listenPopStateControlsDialog();
    // end set of methods to open page as dialog. --------------------------------------------------------------

    // listen form submit (bulk actions).
    filesFilesIndexController.listenFormSubmit();
}, false);