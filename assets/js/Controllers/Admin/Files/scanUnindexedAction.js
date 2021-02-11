/**
 * Scan unindexed files JS for its controller.
 * 
 * @since 0.0.1
 */


class RdbCMSAFilesScanUnindexedController {


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
        const submitBtn = thisForm.querySelector('button[type="submit"]');
        const listingElement = document.getElementById('rdbcmsa-unindexed-files-listing');
        const actionForm = document.getElementById('rdbcmsa-scan-unindexed-files-action-form');
        let noUnlock = false;

        // reset form result placeholder
        thisForm.querySelector('.form-result-placeholder').innerHTML = '';
        // add spinner icon
        thisForm.querySelector('.rdbcmsa-scan-status-icon-placeholder').innerHTML = '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>';
        // lock submit button
        submitBtn.disabled = true;
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

            noUnlock = false;

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
                noUnlock = true;
                // call ajax again to scan for next offset.
                setTimeout(function() {
                    thisClass.ajaxStartScan(event);
                }, 500);
            } else {
                // if there are no more files to display.
                RdbCMSAFilesScanUnindexedObject.offset = 0;
                noUnlock = false;
            }
        })
        .finally(function() {
            if (noUnlock === false) {
                // if allowed to unlock.
                // remove loading icon
                thisForm.querySelector('.loading-icon').remove();
                // unlock submit button
                submitBtn.disabled = false;
            }
        });
    }// ajaxStartScan


    /**
     * Listen on click index selected files.
     * 
     * @private This method was called from `staticInit()`.
     * @returns {undefined}
     */
    listenOnClickIndexFiles() {
        let thisClass = this;
        const thisForm = document.getElementById('rdbcmsa-scan-unindexed-files-action-form');

        thisForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // @todo [rdcms] continue working here.
            console.warn('Not finish yet.');
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