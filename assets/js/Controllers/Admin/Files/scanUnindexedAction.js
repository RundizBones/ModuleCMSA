/**
 * Scan unindexed files JS for its controller.
 * 
 * @since 0.0.1
 */


class RdbCMSAFilesScanUnindexedController {


    /**
     * Class constructor.
     */
    constructor() {
        this.noUnlock = false;
    }// constructor


    /**
     * Make Ajax request to start scan.
     * 
     * @private This method was called from `listenOnClickStartScan()`.
     * @property {object} event The event object.
     * @returns {undefined}
     */
    ajaxStartScan(event) {
        let thisClass = this;
        const thisForm = document.getElementById('rdbcmsa-scan-unindexed-files-form');
        const listingElement = document.getElementById('rdbcmsa-unindexed-files-listing');
        const actionForm = document.getElementById('rdbcmsa-scan-unindexed-files-action-form');

        // reset form result placeholder
        thisForm.querySelector('.form-result-placeholder').innerHTML = '';
        // add spinner icon
        thisForm.querySelector('.rdbcmsa-scan-status-icon-placeholder').innerHTML = '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>';
        // lock submit button
        thisClass.disableSubmitButtons();
        // if offset is 0, reset listing
        if (RdbCMSAFilesScanUnindexedObject.offset === 0) {
            actionForm.classList.remove('rd-hidden');
            listingElement.innerHTML = '';
        }

        let formData = new FormData(thisForm);
        if (RdbaCommon.isset(() => event.submitter)) {
            formData.append(event.submitter.name, event.submitter.value);
        }
        formData.append(RdbCMSAFilesScanUnindexedObject.csrfName, RdbCMSAFilesScanUnindexedObject.csrfKeyPair[RdbCMSAFilesScanUnindexedObject.csrfName]);
        formData.append(RdbCMSAFilesScanUnindexedObject.csrfValue, RdbCMSAFilesScanUnindexedObject.csrfKeyPair[RdbCMSAFilesScanUnindexedObject.csrfValue]);
        formData.append('offset', RdbCMSAFilesScanUnindexedObject.offset);

        RdbaCommon.XHR({
            'url': RdbCMSAFilesScanUnindexedObject.scanUnindexedRestUrl + '?' + new URLSearchParams(_.toArray(formData)).toString(),
            'method': RdbCMSAFilesScanUnindexedObject.scanUnindexedRestMethod,
            'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
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
                RdbCMSAFilesScanUnindexedObject.csrfKeyPair = response.csrfKeyPair;
            }

            thisClass.noUnlock = false;

            return Promise.reject(responseObject);
        })
        .then(function(responseObject) {
            // XHR success.
            let response = responseObject.response;

            let scannedItems = response.scannedItems;

            if (scannedItems.items) {
                const source = document.getElementById('rdbcmsa-files-list-template').innerHTML;
                let template = Handlebars.compile(source);
                listingElement.insertAdjacentHTML('beforeend', template(scannedItems));
            }
            // end testing.

            if (scannedItems.totalFiles > 0) {
                // if there still are files to display.
                console.log('total files: ' + scannedItems.totalFiles + ', unindexed: ' + scannedItems.totalUnIndex);
                // set next start offset.
                RdbCMSAFilesScanUnindexedObject.offset = (parseInt(RdbCMSAFilesScanUnindexedObject.offset) + parseInt(scannedItems.totalFiles));
                thisClass.noUnlock = true;
                // call ajax again to scan for next offset.
                setTimeout(function() {
                    thisClass.ajaxStartScan(event);
                }, 500);
            } else {
                // if there are no more files to display.
                RdbCMSAFilesScanUnindexedObject.offset = 0;
                thisClass.noUnlock = false;
            }

            return Promise.resolve(responseObject);
        })
        .finally(function() {
            if (thisClass.noUnlock === false) {
                // if allowed to unlock.
                // remove loading icon
                thisForm.querySelector('.loading-icon').remove();
                // unlock submit button
                thisClass.enableSubmitButtons();
            }
        });
    }// ajaxStartScan


    /**
     * Enable all submit buttons on this page.
     * 
     * @private This method was called from `ajaxStartScan()`, `listenOnClickIndexFiles()`.
     * @returns {undefined}
     */
    enableSubmitButtons() {
        const submitButtons = document.querySelectorAll('#rdbcmsa-scan-unindexed-files-container button[type="submit"]');

        if (submitButtons && this.noUnlock === false) {
            submitButtons.forEach(function(item, index) {
                item.disabled = false;
            });
        }
    }// enableSubmitButtons


    /**
     * Disable all submit buttons on this page.
     * 
     * @private This method was called from `ajaxStartScan()`, `listenOnClickIndexFiles()`.
     * @returns {undefined}
     */
    disableSubmitButtons() {
        const submitButtons = document.querySelectorAll('#rdbcmsa-scan-unindexed-files-container button[type="submit"]');

        if (submitButtons) {
            submitButtons.forEach(function(item, index) {
                item.disabled = true;
            });
        }
    }// disableSubmitButtons


    /**
     * Listen on click index selected files.
     * 
     * @private This method was called from `staticInit()`.
     * @returns {undefined}
     */
    listenOnClickIndexFiles() {
        let thisClass = this;
        const thisForm = document.getElementById('rdbcmsa-scan-unindexed-files-action-form');
        const listingElement = document.getElementById('rdbcmsa-unindexed-files-listing');

        thisForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // reset form result placeholder
            thisForm.querySelector('.form-action-result-placeholder').innerHTML = '';
            // add spinner icon
            thisForm.querySelector('.rdbcmsa-unindexed-files-action-status-icon-placeholder').innerHTML = '<i class="fas fa-spinner fa-pulse fa-fw action-loading-icon" aria-hidden="true"></i>';
            // lock submit button
            thisClass.disableSubmitButtons();

            let formData = new FormData(thisForm);
            if (RdbaCommon.isset(() => event.submitter)) {
                formData.append(event.submitter.name, event.submitter.value);
            }
            formData.append(RdbCMSAFilesScanUnindexedObject.csrfName, RdbCMSAFilesScanUnindexedObject.csrfKeyPair[RdbCMSAFilesScanUnindexedObject.csrfName]);
            formData.append(RdbCMSAFilesScanUnindexedObject.csrfValue, RdbCMSAFilesScanUnindexedObject.csrfKeyPair[RdbCMSAFilesScanUnindexedObject.csrfValue]);
            formData.delete('realPathHash[]');
            thisForm.querySelectorAll('input[type="checkbox"]:checked').forEach(function(item, index) {
                formData.append('realPathHash[' + index + ']', item.value);
                formData.append('file_folder[' + index + ']', item.dataset.file_folder);
                formData.append('file_name[' + index + ']', item.dataset.file_name);
                formData.append('realPath[' + index + ']', item.dataset.realPath);
            });

            RdbaCommon.XHR({
                'url': RdbCMSAFilesScanUnindexedObject.scanUnindexedAddRestUrl,
                'method': RdbCMSAFilesScanUnindexedObject.scanUnindexedAddRestMethod,
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
                    thisForm.querySelector('.form-action-result-placeholder').innerHTML = alertBox;
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbCMSAFilesScanUnindexedObject.csrfKeyPair = response.csrfKeyPair;
                }

                return Promise.reject(responseObject);
            })
            .then(function(responseObject) {
                // XHR success.
                let response = responseObject.response;

                if (response && response.formResultMessage) {
                    let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                    let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                    thisForm.querySelector('.form-action-result-placeholder').innerHTML = alertBox;
                }

                if (RdbaCommon.isset(() => response.indexAllResult.successHash)) {
                    response.indexAllResult.successHash.forEach(function(item, index) {
                        let indexedCheckbox = listingElement.querySelector('input[type="checkbox"][value="' + item + '"]');
                        if (indexedCheckbox) {
                            indexedCheckbox.closest('li').remove();
                        }
                    });
                }

                return Promise.resolve(responseObject);
            })
            .finally(function() {
                // remove loading icon
                thisForm.querySelector('.action-loading-icon').remove();
                // unlock submit button
                thisClass.enableSubmitButtons();
            });
        });
    }// listenOnClickIndexFiles


    /**
     * Listen on click start scan and call to ajax start scan.
     * 
     * @private This method was called from `staticInit()`.
     * @returns {undefined}
     */
    listenOnClickStartScan() {
        let thisClass = this;
        const thisForm = document.getElementById('rdbcmsa-scan-unindexed-files-form');

        thisForm.addEventListener('submit', function(event) {
            event.preventDefault();

            thisClass.ajaxStartScan(event);
        });
    }// listenOnClickStartScan


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    static staticInit() {
        let thisClass = new this() ;

        // listen on click start scan and do it.
        thisClass.listenOnClickStartScan();
        // listen on click index selected files.
        thisClass.listenOnClickIndexFiles();
    }// staticInit


}// RdbCMSAFilesScanUnindexedController


document.addEventListener('DOMContentLoaded', function() {
    // equivalent to jQuery document ready.
    // this will be working on normal page load (non AJAX).
    RdbCMSAFilesScanUnindexedController.staticInit();
}, false);