/**
 * Add contents category.
 */


class RdbCMSACategoriesAddController {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        if (typeof(options) === 'undefined') {
            options = {};
        }

        if (
            !RdbaCommon.isset(() => options.formIDSelector) || 
            (RdbaCommon.isset(() => options.formIDSelector) && _.isEmpty(options.formIDSelector))
        ) {
            options.formIDSelector = '#rdbcmsa-add-contents-category-form'
        }
        this.formIDSelector = options.formIDSelector;

        if (
            !RdbaCommon.isset(() => options.dialogIDSelector) || 
            (RdbaCommon.isset(() => options.dialogIDSelector) && _.isEmpty(options.dialogIDSelector))
        ) {
            options.dialogIDSelector = '#rdbcmsa-contents-categories-dialog'
        }
        this.dialogIDSelector = options.dialogIDSelector;

        if (
            !RdbaCommon.isset(() => options.datatableIDSelector) || 
            (RdbaCommon.isset(() => options.datatableIDSelector) && _.isEmpty(options.datatableIDSelector))
        ) {
            options.datatableIDSelector = '#contentsCategoriesTable';
        }
        this.datatableIDSelector = options.datatableIDSelector;
    }// constructor


    /**
     * Listen on form submit and make it XHR.
     * 
     * @private This method was called from `staticInit()`.
     * @returns {undefined}
     */
    listenFormSubmit() {
        let thisClass = this;

        document.addEventListener('submit', function(event) {
            if (
                event.target && 
                '#' + event.target.id === thisClass.formIDSelector
            ) {
                event.preventDefault();

                let thisForm = event.target;
                let submitBtn = thisForm.querySelector('button[type="submit"]');

                // set csrf again to prevent firefox form cached.
                if (!RdbCMSACategoriesIndexObject.isInDataTablesPage) {
                    thisForm.querySelector('#rdba-form-csrf-name').value = RdbCMSACategoriesIndexObject.csrfKeyPair[RdbCMSACategoriesIndexObject.csrfName];
                    thisForm.querySelector('#rdba-form-csrf-value').value = RdbCMSACategoriesIndexObject.csrfKeyPair[RdbCMSACategoriesIndexObject.csrfValue];
                }

                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.action-status-placeholder').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button.
                submitBtn.disabled = true;

                let formData = new FormData(thisForm);

                RdbaCommon.XHR({
                    'url': RdbCMSACategoriesIndexObject.addCategoryRESTUrl,
                    'method': RdbCMSACategoriesIndexObject.addCategoryRESTMethod,
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
                            // if this is opening in dialog.
                            // close the dialog and reload page.
                            document.querySelector(thisClass.dialogIDSelector + ' [data-dismiss="dialog"]').click();
                            // reload datatable.
                            jQuery(thisClass.datatableIDSelector).DataTable().ajax.reload(null, false);
                        } else {
                            // this is in its page, redirect to the redirect back url.
                            window.location.href = response.redirectBack;
                        }
                    }

                    if (response && response.formResultMessage) {
                        // if there is form result message, display it.
                        RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbCMSACategoriesIndexObject.csrfKeyPair = response.csrfKeyPair;
                        if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                            thisForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                            thisForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                        }
                    }

                    return Promise.resolve(responseObject);
                }, function(error) {
                    // prevent Uncaught (in promise) error.
                })
                .finally(function() {
                    // remove loading icon
                    thisForm.querySelector('.loading-icon').remove();
                    // unlock submit button
                    submitBtn.disabled = false;
                });
            }
        });
    }// listenFormSubmit

    /**
     * Listen on ajax get parent done and do following tasks.
     * 
     * - activate tinymce.
     * 
     * @param {RdbCMSACategoriesCommonActions} commonActions  The common actions class.
     * @returns {undefined}
     */
    listenGetParentsDone(commonActions) {
        let thisClass = this;

        if (!document.querySelector(thisClass.formIDSelector)) {
            // if not in add page.
            // stop! don't do anything here. this is for prevent double listener.
            return ;
        }

        document.addEventListener('rdbcmsa.categoriesediting.ajaxgetparents.done', function(event) {
            if (!document.querySelector(thisClass.formIDSelector)) {
                // if not in add page.
                // stop! don't do anything here. this is for prevent duoble call to functions below.
                return ;
            }

            // activate tinymce
            commonActions.activateContentEditor();
        });
    }// listenGetParentsDone


    /**
     * Static initialize the class.
     * 
     * This is useful for ajax page.
     * 
     * @returns {undefined}
     */
    static staticInit() {
        let thisClass = new this() ;
        let rdbcmsaCategoriesCommonActionsClass = new RdbCMSACategoriesCommonActions({
            'formIDSelector': thisClass.formIDSelector,
            'dialogIDSelector': thisClass.dialogIDSelector
        });

        // listen on dialog opened and then ajax get parent categories.
        rdbcmsaCategoriesCommonActionsClass.listenDialogOpened();
        // listen on dialog closed and destroy tinymce.
        rdbcmsaCategoriesCommonActionsClass.listenDialogClosed();
        // listen on type URL and correct to safe URL string.
        rdbcmsaCategoriesCommonActionsClass.listenUrlToCorrectUrl();

        // listen ajax get parents done and do tasks.
        thisClass.listenGetParentsDone(rdbcmsaCategoriesCommonActionsClass);

        // listen on form submit and make it AJAX request.
        thisClass.listenFormSubmit();
   }// staticInit


}// RdbCMSACategoriesAddController


document.addEventListener('rdbcmsa.contents-categories.editing.newinit', function() {
    // listen on new assets loaded.
    // this will be working on js loaded via AJAX.
    // must use together with `document.addEventListener('DOMContentLoaded')`
    if (
        RdbaCommon.isset(() => event.detail.rdbaUrlNoDomain) && 
        event.detail.rdbaUrlNoDomain.includes('/add') !== false
    ) {
        RdbCMSACategoriesAddController.staticInit();
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // equivalent to jQuery document ready.
    // this will be working on normal page load (non AJAX).
    RdbCMSACategoriesAddController.staticInit();
}, false);