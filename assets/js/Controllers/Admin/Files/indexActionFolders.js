/**
 * Folders management for Files management page.
 */


class RdbCMSAFilesIndexControllerFolders {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        this.folderDialogSelector = '#rdbcmsa-folder-name-dialog';
        this.folderDialogFolderToRenameSelector = '#rdbcmsa-folder-name-dialog-folder-to-rename';
        this.folderDialogIsCreateNewSelector = '#rdbcmsa-folder-name-dialog-is-createnew';
        this.folderDialogNewFolderInputSelector = '#rdbcmsa-folder-name-dialog-new-folder-name';
        this.folderDialogNewFolderInSelector = '#rdbcmsa-folder-name-dialog-new-folder-in';

        this.currentFolderFilterSelector = '#rdbcmsa-files-filter-folder';

        this.folderListSelector = '#rdbcmsa-files-folders-list-container';
        this.folderNewButtonSelector = '#rdbcmsa-files-new-folder';
        this.folderReloadButtonSelector = '#rdbcmsa-files-reload-folder';
    }// constructor


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        // load folders.
        RdbCMSAFilesIndexControllerFolders.reloadFolders();

        // listen click folder name to set filder.
        this.listenClickFolderNameToSetFilter();
        // listen click on new/rename folder name then open dialog.
        this.listenClickOpenFolderNameDialog();
        // listen click on save button on the dialog and ajax submit.
        this.listenClickDialogSaveButton();
        // listen click on delete then ajax delete.
        this.listenClickDeleteFolder();
    }// init


    /**
     * Listen on click delete and ask for confirm then ajax delete folder and everything in it.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenClickDeleteFolder() {
        let thisClass = this;

        document.addEventListener('click', function(event) {
            if (
                RdbaCommon.isset(() => event.currentTarget.activeElement) &&
                event.currentTarget.activeElement.classList.contains('rdbcmsa-files-delete-folder')
            ) {
                event.preventDefault();

                let thisButton = event.currentTarget.activeElement;
                if (typeof(thisButton.dataset.folderrelpath) === 'undefined') {
                    console.error('user clicked on undefined object.');
                    return ;
                }

                let confirmVal = confirm(RdbCMSAFilesCommonObject.txtConfirmDeleteFolder + "\n\n" + RdbCMSAFilesCommonObject.rootPublicFolderName + '/' + thisButton.dataset.folderrelpath + "\n\n" + RdbCMSAFilesCommonObject.txtAllFilesInWillBeDeleted);

                let formData = new FormData();
                formData.append(RdbCMSAFilesCommonObject.csrfName, RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfName]);
                formData.append(RdbCMSAFilesCommonObject.csrfValue, RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfValue]);
                formData.append('folderrelpath', thisButton.dataset.folderrelpath);

                if (confirmVal === true) {
                    RdbaCommon.XHR({
                        'url': RdbCMSAFilesCommonObject.deleteFolderRESTUrl,
                        'method': RdbCMSAFilesCommonObject.deleteFolderRESTMethod,
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
                            RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
                        }

                        return Promise.reject(responseObject);
                    })
                    .then(function(responseObject) {
                        // XHR success.
                        let response = responseObject.response;

                        // reset filter folder
                        let filterFolder = document.querySelector(thisClass.currentFolderFilterSelector);
                        if (filterFolder) {
                            filterFolder.value = '';
                        }
                        // reload folder list.
                        RdbCMSAFilesIndexControllerFolders.reloadFolders();
                        // reload files data table.
                        jQuery('#filesListItemsTable').DataTable().ajax.reload(null, false);

                        if (typeof(response) !== 'undefined') {
                            if (typeof(response.formResultMessage) !== 'undefined') {
                                RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                            }
                        }

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
                        }

                        return Promise.resolve(responseObject);
                    });
                }
            }
        });
    }// listenClickDeleteFolder


    /**
     * Listen on click save button on the dialog.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenClickDialogSaveButton() {
        let thisClass = this;

        document.addEventListener('click', function(event) {
            if (
                RdbaCommon.isset(() => event.target) &&
                event.target.id === 'rdbcmsa-folder-name-dialog-save-button'
            ) {
                event.preventDefault();

                let submitBtn = event.target;
                let folderToRenameInput = document.querySelector(thisClass.folderDialogFolderToRenameSelector);
                let isCreateNewInput = document.querySelector(thisClass.folderDialogIsCreateNewSelector);
                let newFolderInInput = document.querySelector(thisClass.folderDialogNewFolderInSelector);
                let newFolderNameInput = document.querySelector(thisClass.folderDialogNewFolderInputSelector);

                // lock button
                submitBtn.disabled = true;

                let formData = new FormData();
                let taskUrl, taskMethod;
                if (isCreateNewInput.value === 'true') {
                    taskUrl = RdbCMSAFilesCommonObject.newFolderRESTUrl;
                    taskMethod = RdbCMSAFilesCommonObject.newFolderRESTMethod;
                    formData.append('new_folder_in', newFolderInInput.value);
                } else {
                    taskUrl = RdbCMSAFilesCommonObject.renameFolderRESTUrl;
                    taskMethod = RdbCMSAFilesCommonObject.renameFolderRESTMethod;
                    formData.append('folder_to_rename', folderToRenameInput.value);
                }

                formData.append(RdbCMSAFilesCommonObject.csrfName, RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfName]);
                formData.append(RdbCMSAFilesCommonObject.csrfValue, RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfValue]);
                formData.append('new_folder_name', newFolderNameInput.value);

                RdbaCommon.XHR({
                    'url': taskUrl,
                    'method': taskMethod,
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
                        RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    // trigger close dialog.
                    document.querySelector(thisClass.folderDialogSelector + ' [data-dismiss="dialog"]').click();
                    // reload folder list.
                    RdbCMSAFilesIndexControllerFolders.reloadFolders();
                    // reload files data table.
                    jQuery('#filesListItemsTable').DataTable().ajax.reload(null, false);

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
                    // unlock submit button
                    submitBtn.disabled = false;
                });
            }
        });
    }// listenClickDialogSaveButton


    /**
     * Listen on click folder name to set filter value.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenClickFolderNameToSetFilter() {
        let thisClass = this;
        let filterFolder = document.querySelector(this.currentFolderFilterSelector);

        document.addEventListener('click', function(event) {
            if (
                RdbaCommon.isset(() => event.target) &&
                event.target.classList.contains('rdbcmsa-files-folders-list-link')
            ) {
                event.preventDefault();
                let thisLink = event.target;

                if (filterFolder) {
                    // set filter value
                    filterFolder.value = thisLink.dataset.folderrelpath;
                    // mark current folder
                    thisClass.markCurrentFolder();
                    // trigger click submit filter.
                    let filterButton = document.querySelector('#rdba-datatables-filter-button');
                    if (filterButton) {
                        filterButton.click();
                    }
                }
            }
        });
        
    }// listenClickFolderNameToSetFilter


    /**
     * Listen on click new folder, rename folder then open dialog.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenClickOpenFolderNameDialog() {
        let thisClass = this;

        document.addEventListener('click', function(event) {
            if (
                RdbaCommon.isset(() => event.target.closest('button').id) &&
                event.target.closest('button').id === 'rdbcmsa-files-new-folder' ||
                (
                    RdbaCommon.isset(() => event.currentTarget.activeElement.classList) &&
                    event.currentTarget.activeElement.classList.contains('rdbcmsa-files-rename-folder')
                )
            ) {
                event.preventDefault();
                let thisButton = event.target.closest('button');
                let currentFolderFilter = document.querySelector(thisClass.currentFolderFilterSelector);
                let dialogLabel = document.getElementById('rdbcmsa-folder-name-dialog-label');
                let displayFolderToRename = document.getElementById('rdbcmsa-folder-name-dialog-folder-willbe-rename');
                let displayNewFolderIn = document.getElementById('rdbcmsa-folder-name-dialog-new-folder-in-display');
                let newFolderInInput = document.querySelector(thisClass.folderDialogNewFolderInSelector);
                let folderToRenameInput = document.querySelector(thisClass.folderDialogFolderToRenameSelector);
                let newFolderNameInput = document.querySelector(thisClass.folderDialogNewFolderInputSelector);

                // detect is it create new folder or rename.
                let isCreateNew = false;// is create new folder?
                if (thisButton.id === 'rdbcmsa-files-new-folder') {
                    isCreateNew = true;
                }
                let isCreateNewInput = document.querySelector(thisClass.folderDialogIsCreateNewSelector);
                if (isCreateNewInput) {
                    isCreateNewInput.value = isCreateNew;
                }

                // set dialog label (header).
                if (dialogLabel) {
                    dialogLabel.innerText = thisButton.title;
                }

                // set currently selected folder for display in parts
                if (RdbaCommon.isset(() => thisButton.dataset.folderrelpath)) {
                    // if there is data-folderrelpath="xxx" attribute.
                    if (displayFolderToRename) {
                        displayFolderToRename.innerText = thisButton.dataset.folderrelpath;
                    }
                    if (folderToRenameInput) {
                        folderToRenameInput.value = thisButton.dataset.folderrelpath;
                    }
                    if (newFolderNameInput) {
                        if (isCreateNew === true) {
                            newFolderNameInput.value = thisButton.dataset.folderrelpath;
                        } else {
                            newFolderNameInput.value = thisButton.dataset.foldername;
                        }
                    }
                } else {
                    // if there is NO data-folderrelpath="xxx" attribute.
                    if (displayFolderToRename) {
                        displayFolderToRename.innerText = '';
                    }
                    if (folderToRenameInput) {
                        folderToRenameInput.value = '';
                    }
                    if (newFolderNameInput) {
                        newFolderNameInput.value = '';
                    }
                }
                if (newFolderInInput) {
                    newFolderInInput.value = currentFolderFilter.value;
                }
                if (displayNewFolderIn) {
                    displayNewFolderIn.innerText = currentFolderFilter.value;
                }

                // show or hide new folder or rename folder depend on isCreateNew.
                if (isCreateNew === true) {
                    // if creating new folder.
                    document.querySelectorAll('.form-fields-for-new-folder').forEach(function(item, index) {
                        item.classList.remove('rd-hidden');
                        item.disabled = false;
                    });
                    document.querySelectorAll('.form-fields-for-rename-folder').forEach(function(item, index) {
                        item.classList.add('rd-hidden');
                        item.disabled = true;
                    });
                } else {
                    // if renaming the currently exists folder.
                    document.querySelectorAll('.form-fields-for-new-folder').forEach(function(item, index) {
                        item.classList.add('rd-hidden');
                        item.disabled = true;
                    });
                    document.querySelectorAll('.form-fields-for-rename-folder').forEach(function(item, index) {
                        item.classList.remove('rd-hidden');
                        item.disabled = false;
                    });
                }

                let rdtaDialog = new RDTADialog();
                rdtaDialog.activateDialog(thisClass.folderDialogSelector);
            }
        });
    }// listenClickOpenFolderNameDialog


    /**
     * Mark current folder on folder list by get current folder from filter value.
     * 
     * @private This method was called from `reloadFolders()`, `listenClickFolderNameToSetFilter()` methods.
     * @returns {undefined}
     */
    markCurrentFolder() {
        let currentFolderFilter = document.querySelector(this.currentFolderFilterSelector);
        let folderListElement = document.querySelector(this.folderListSelector);

        if (currentFolderFilter && folderListElement) {
            // remove all current folder icon(s).
            let currentFolderClass = document.querySelectorAll('.rdbcmsa-files-folders-list-link.current');
            if (currentFolderClass) {
                currentFolderClass.forEach(function(item, index) {
                    item.classList.remove('current');
                });
            }
            // mark current folder icon.
            let currentFolderFilterValue = currentFolderFilter.value;
            let folderRelpath = folderListElement.querySelector('[data-folderrelpath="' + currentFolderFilterValue + '"]');
            if (folderRelpath) {
                folderRelpath.classList.add('current');
            }
        }
    }// markCurrentFolder


    /**
     * Reload folders.
     * 
     * This method was called from `init()`, `listenClickDialogSaveButton()`, `listenClickDeleteFolder()` methods and reload button.
     * 
     * @returns {undefined}
     */
    static reloadFolders() {
        let thisClass = new RdbCMSAFilesIndexControllerFolders();
        let reloadBtn = document.querySelector(thisClass.folderReloadButtonSelector);
        let reloadIcon;
        let newFolderBtn = document.querySelector(thisClass.folderNewButtonSelector);
        let folderListElement = document.querySelector(thisClass.folderListSelector);

        // lock button
        if (newFolderBtn) {
            newFolderBtn.disabled = true;
        }
        // animate reload button
        if (reloadBtn) {
            reloadIcon = reloadBtn.querySelector('.reload-icon');
            if (reloadIcon) {
                reloadIcon.classList.add('fa-spin');
            }
        }

        if (RdbCMSAFilesCommonObject.debug === true) {
            console.log('Reloading folders.');
        }

        let formData = new FormData();
        formData.append(RdbCMSAFilesCommonObject.csrfName, RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfName]);
        formData.append(RdbCMSAFilesCommonObject.csrfValue, RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfValue]);
        let queryString = new URLSearchParams(_.toArray(formData)).toString();

        RdbaCommon.XHR({
            'url': RdbCMSAFilesCommonObject.getFoldersRESTUrl + '?' + queryString,
            'method': RdbCMSAFilesCommonObject.getFoldersRESTMethod,
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
                RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.reject(responseObject);
        })
        .then(function(responseObject) {
            // XHR success.
            let response = responseObject.response;

            if (RdbCMSAFilesCommonObject.debug === true) {
                console.log('Responded from reload folders.');
            }

            // reload list.
            if (typeof(response.list) !== 'undefined' && folderListElement) {
                // reset folder list
                folderListElement.querySelectorAll('li:not(:first-child)').forEach(function(item, index) {
                    item.remove();
                });
                folderListElement.querySelectorAll('ul').forEach(function(item, index) {
                    item.remove();
                });

                // render to html
                // @link http://jsfiddle.net/adamboduch/5yt6M/ Original source code.
                // @link http://www.boduch.ca/2014/03/recursive-list-building-with-handlebars.html Original source code.
                let source = document.getElementById('rdbcmsa-files-folders-listing-main').innerHTML;
                let template = Handlebars.compile(source);
                Handlebars.registerPartial('list', document.getElementById('rdbcmsa-files-folders-listing').innerHTML);
                let html = template({'children': response.list});
                folderListElement.querySelector('li').insertAdjacentHTML('beforeend', html);

                thisClass.markCurrentFolder();
            }

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
            if (reloadIcon) {
                reloadIcon.classList.remove('fa-spin');
            }
            // unlock button
            if (newFolderBtn) {
                newFolderBtn.disabled = false;
            }
        });
    }// reloadFolders


}// RdbCMSAFilesIndexControllerFolders


document.addEventListener('DOMContentLoaded', function() {
    let rdbcmsaFilesIndexControllerFoldersClass = new RdbCMSAFilesIndexControllerFolders();
    // init the class.
    rdbcmsaFilesIndexControllerFoldersClass.init();
}, false);