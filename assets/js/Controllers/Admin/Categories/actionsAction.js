/**
 * Bulk actions JS for its controller.
 */


class RdbCMSACategoriesActionsController {


    /**
     * Listen on form submit.
     * 
     * @returns {undefined}
     */
    listenFormSubmit() {
        document.addEventListener('submit', function(event) {
            if (event.target && event.target.id === 'rdbcmsa-actions-contents-categories-form') {
                event.preventDefault();

                let thisForm = event.target;

                // set csrf again to prevent firefox form cached.
                if (!RdbCMSACategoriesIndexObject.isInDataTablesPage) {
                    thisForm.querySelector('#rdba-form-csrf-name').value = RdbCMSACategoriesIndexObject.csrfKeyPair[RdbCMSACategoriesIndexObject.csrfName];
                    thisForm.querySelector('#rdba-form-csrf-value').value = RdbCMSACategoriesIndexObject.csrfKeyPair[RdbCMSACategoriesIndexObject.csrfValue];
                }

                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                thisForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

                let formData = new FormData(thisForm);
                let formUrl = '';
                let formMethod = '';

                let bulkAction = (thisForm.querySelector('#bulk-action') ? thisForm.querySelector('#bulk-action').value : '');
                if (bulkAction === 'delete') {
                    formUrl = RdbCMSACategoriesIndexObject.deleteCategoryRESTUrlBase + '/' + thisForm.querySelector('#bulk-tids').value;
                    formMethod = RdbCMSACategoriesIndexObject.deleteCategoryRESTMethod;
                }

                RdbaCommon.XHR({
                    'url': formUrl,
                    'method': formMethod,
                    'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'data': new URLSearchParams(_.toArray(formData)).toString(),
                    'dataType': 'json'
                })
                .catch(function(responseObject) {
                    // XHR failed.
                    let response = responseObject.response;
                    console.error(responseObject);

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                            let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                            thisForm.querySelector('.form-result-placeholder').innerHTML = alertBox;
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbCMSACategoriesIndexObject.csrfKeyPair = response.csrfKeyPair;
                        if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                            thisForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                            thisForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                        }
                    }

                    return Promise.reject(responseObject);
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    if (response.redirectBack) {
                        if (RdbCMSACategoriesIndexObject && RdbCMSACategoriesIndexObject.isInDataTablesPage && RdbCMSACategoriesIndexObject.isInDataTablesPage === true) {
                            // this is opening in dialog, close the dialog and reload page.
                            document.querySelector('#rdbcmsa-contents-categories-dialog [data-dismiss="dialog"]').click();
                            //window.location.reload();// use datatables reload instead.
                            jQuery('#contentsCategoriesTable').DataTable().ajax.reload(null, false);
                        } else {
                            // this is in its page, redirect to the redirect back url.
                            window.location.href = response.redirectBack;
                        }
                    }

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                            let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                            thisForm.querySelector('.form-result-placeholder').innerHTML = alertBox;
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbCMSACategoriesIndexObject.csrfKeyPair = response.csrfKeyPair;
                        if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                            thisForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                            thisForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                        }
                    }

                    return Promise.resolve(responseObject);
                })
                .finally(function() {
                    // remove loading icon
                    thisForm.querySelector('.loading-icon').remove();
                    // unlock submit button
                    thisForm.querySelector('.rdba-submit-button').removeAttribute('disabled');
                });
            }
        }, false);
    }// listenFormSubmit


    /**
     * Static initialize the class.
     * 
     * This is useful for ajax page.
     * 
     * @returns {undefined}
     */
    static staticInit() {
        let rdbcmsaCategoriesActionsController = new RdbCMSACategoriesActionsController();

        // listen on form submit and make ajax submit.
        rdbcmsaCategoriesActionsController.listenFormSubmit();
    }// staticInit

    
}// RdbCMSACategoriesActionsController


document.addEventListener('rdcmsa.contents-categories.editing.newinit', function() {
    // listen on new assets loaded.
    // this will be working on js loaded via AJAX.
    // must use together with `document.addEventListener('DOMContentLoaded')`
    if (
        RdbaCommon.isset(() => event.detail.rdbaUrlNoDomain) && 
        event.detail.rdbaUrlNoDomain.includes('/actions') !== false
    ) {
        RdbCMSACategoriesActionsController.staticInit();
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // equivalent to jQuery document ready.
    // this will be working on normal page load (non AJAX).
    RdbCMSACategoriesActionsController.staticInit();
}, false);