/**
 * URL aliases - edit page JS for its controller.
 */


class RdbCMSAToolsURLAliasesEditController {


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
            options.formIDSelector = '#rdbcmsaurlaliases-edit-form';
        }
        this.formIDSelector = options.formIDSelector;

        if (
            !RdbaCommon.isset(() => options.dialogIDSelector) || 
            (RdbaCommon.isset(() => options.dialogIDSelector) && _.isEmpty(options.dialogIDSelector))
        ) {
            options.dialogIDSelector = '#rdbcmsaToolsUrlAliases-editing-dialog';
        }
        this.dialogIDSelector = options.dialogIDSelector;

        if (
            !RdbaCommon.isset(() => options.datatableIDSelector) || 
            (RdbaCommon.isset(() => options.datatableIDSelector) && _.isEmpty(options.datatableIDSelector))
        ) {
            options.datatableIDSelector = '#rdbcmsaToolsURLAliasesTable';
        }
        this.datatableIDSelector = options.datatableIDSelector;
    }// constructor


    /**
     * XHR get form data and set it to form fields.
     * 
     * This method was called from `staticInit()` method and outside.
     * 
     * @returns {undefined}
     */
    ajaxGetFormData() {
        if (!document.querySelector(this.formIDSelector)) {
            // if no editing form, do not working to waste cpu.
            return false;
        }

        let thisClass = this;
        let thisForm = document.querySelector(this.formIDSelector);
        let formId = thisForm.querySelector('#alias_id');

        RdbaCommon.XHR({
            'url': RdbCMSAToolsURLAliasesIndexObject.getAliasRESTUrlBase + '/' + (formId ? formId.value : ''),
            'method': RdbCMSAToolsURLAliasesIndexObject.getAliasRESTMethod,
            'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
            'dataType': 'json'
        })
        .catch(function(responseObject) {
            console.error('[rdbcmsa]: ', responseObject);
            let response = (responseObject ? responseObject.response : {});

            if (typeof(response) !== 'undefined') {
                if (typeof(response.formResultMessage) !== 'undefined') {
                    let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                    let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                    thisForm.querySelector('.form-result-placeholder').innerHTML = alertBox;
                }
            }

            if (responseObject && responseObject.status && responseObject.status === 404) {
                // if not found.
                // disable form.
                let formElements = (thisForm ? thisForm.elements : []);
                for (var i = 0, len = formElements.length; i < len; ++i) {
                    formElements[i].disabled = true;
                }// endfor;
            }
        })
        .then(function(responseObject) {
            let response = (responseObject ? responseObject.response : {});
            let resultRow = response.result;

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbCMSAToolsURLAliasesIndexObject.csrfKeyPair = response.csrfKeyPair;
                if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                    thisForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                    thisForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                }
            }

            // set the data that have got via ajax to form fields.
            for (let prop in resultRow) {
                if (
                    Object.prototype.hasOwnProperty.call(resultRow, prop) && 
                    document.getElementById(prop) && 
                    prop !== 'alias_id' &&
                    resultRow[prop] !== null
                ) {
                    document.getElementById(prop).value = RdbaCommon.unEscapeHtml(resultRow[prop]);
                }
            }// endfor;
        });
    }// ajaxGetFormData



    /**
     * Listen on form submit and make it XHR.
     * 
     * @returns {undefined}
     */
    listenFormSubmit() {
        let thisClass = this;

        document.addEventListener('submit', function(event) {
            if (
                event.target && 
                event.target.getAttribute('id') && 
                '#' + event.target.getAttribute('id') === thisClass.formIDSelector
            ) {
                event.preventDefault();

                let thisForm = event.target;
                let submitBtn = thisForm.querySelector('button[type="submit"]');
                let formId = thisForm.querySelector('#alias_id');

                // set csrf again to prevent firefox form cached.
                if (!RdbCMSAToolsURLAliasesIndexObject.isInDataTablesPage) {
                    thisForm.querySelector('#rdba-form-csrf-name').value = RdbCMSAToolsURLAliasesIndexObject.csrfKeyPair[RdbCMSAToolsURLAliasesIndexObject.csrfName];
                    thisForm.querySelector('#rdba-form-csrf-value').value = RdbCMSAToolsURLAliasesIndexObject.csrfKeyPair[RdbCMSAToolsURLAliasesIndexObject.csrfValue];
                }

                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                submitBtn.disabled = true;

                let formData = new FormData(thisForm);

                RdbaCommon.XHR({
                    'url': RdbCMSAToolsURLAliasesIndexObject.editAliasRESTUrlBase + '/' + (formId ? formId.value : ''),
                    'method': RdbCMSAToolsURLAliasesIndexObject.editAliasRESTMethod,
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
                        RdbCMSAToolsURLAliasesIndexObject.csrfKeyPair = response.csrfKeyPair;
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
                        if (RdbCMSAToolsURLAliasesIndexObject && RdbCMSAToolsURLAliasesIndexObject.isInDataTablesPage && RdbCMSAToolsURLAliasesIndexObject.isInDataTablesPage === true) {
                            // this is opening in dialog, close the dialog and reload page.
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
                        RdbCMSAToolsURLAliasesIndexObject.csrfKeyPair = response.csrfKeyPair;
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
        let thisClass = new this() ;
        let rdbcmsaCategoriesCommonActionsClass = new RdbCMSACategoriesCommonActions({
            'formIDSelector': thisClass.formIDSelector,
            'dialogIDSelector': thisClass.dialogIDSelector
        });

        // listen on type URL and correct to safe URL string.
        rdbcmsaCategoriesCommonActionsClass.listenUrlToCorrectUrl();

        // ajax get form data.
        thisClass.ajaxGetFormData();
        // listen on form submit and make it AJAX request.
        thisClass.listenFormSubmit();
   }// staticInit


}// RdbCMSAToolsURLAliasesEditController


document.addEventListener('toolsurlaliasesdialog.editing.newinit', function() {
    // listen on new assets loaded.
    // this will be working on js loaded via AJAX.
    // must use together with `document.addEventListener('DOMContentLoaded')`
    if (
        RdbaCommon.isset(() => event.detail.rdbaUrlNoDomain) && 
        event.detail.rdbaUrlNoDomain.includes('/edit') !== false
    ) {
        RdbCMSAToolsURLAliasesEditController.staticInit();
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // equivalent to jQuery document ready.
    // this will be working on normal page load (non AJAX).
    RdbCMSAToolsURLAliasesEditController.staticInit();
}, false);
document.addEventListener('toolsurlaliasesdialog.editing.reinit', function() {
    // listen on re-open ajax dialog (assets is already loaded before).
    // this is required when... user click edit > save > close dialog > click edit other > now it won't load if there is no this listener.
    if (
        RdbaCommon.isset(() => event.detail.rdbaUrlNoDomain) && 
        event.detail.rdbaUrlNoDomain.includes('/edit') !== false
    ) {
        let rdbcmsaToolsURLAliasesEditControllerClass = new RdbCMSAToolsURLAliasesEditController();
        // ajax get form data.
        rdbcmsaToolsURLAliasesEditControllerClass.ajaxGetFormData();
    }
});