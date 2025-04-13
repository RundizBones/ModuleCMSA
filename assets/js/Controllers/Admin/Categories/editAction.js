/**
 * Edit contents category.
 */


class RdbCMSACategoriesEditController {


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
            options.formIDSelector = '#rdbcmsa-edit-contents-category-form'
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
     * Ajax get form data for the form.
     * 
     * @returns {undefined}
     */
    ajaxGetFormData() {
        let thisClass = this;
        let thisForm = document.querySelector(thisClass.formIDSelector);

        if (!thisForm) {
            // if no editing form, do not working to waste cpu.
            return false;
        }

        let tid = document.getElementById('tid').value;

        // set csrf again to prevent firefox form cached.
        if (!RdbCMSACategoriesIndexObject.isInDataTablesPage) {
            thisForm.querySelector('#rdba-form-csrf-name').value = RdbCMSACategoriesIndexObject.csrfKeyPair[RdbCMSACategoriesIndexObject.csrfName];
            thisForm.querySelector('#rdba-form-csrf-value').value = RdbCMSACategoriesIndexObject.csrfKeyPair[RdbCMSACategoriesIndexObject.csrfValue];
        }

        return RdbaCommon.XHR({
            'url': RdbCMSACategoriesIndexObject.getCategoryRESTUrlBase + '/' + tid,
            'method': RdbCMSACategoriesIndexObject.getCategoryRESTMethod
        })
        .then(function(responseObject) {
            let response = (responseObject ? responseObject.response : {});
            let categoryRow = response.category;

            // set the data that have got via ajax to form fields.
            for (let prop in categoryRow) {
                if (
                    Object.prototype.hasOwnProperty.call(categoryRow, prop) && // has property
                    document.getElementById(prop) && 
                    prop !== 'tid' &&
                    categoryRow[prop] !== null
                ) {
                    if (prop === 't_description') {
                        // if description column, this field in html is allowed for HTML then don't unescape or the HTML elements will be messed with textarea.
                        document.getElementById(prop).value = categoryRow[prop];
                    } else {
                        document.getElementById(prop).value = RdbaCommon.unEscapeHtml(categoryRow[prop]);
                    }
                }
            }// endfor;

            let parent_id = thisForm.querySelectorAll('#parent_id option');
            if (parent_id) {
                parent_id.forEach(function(item, index) {
                    if (
                        parseInt(item.value) === parseInt(categoryRow.tid) ||
                        (
                            parseInt(item.dataset.t_left) > parseInt(categoryRow.t_left) && 
                            parseInt(item.dataset.t_right) < parseInt(categoryRow.t_right)
                        )
                    ) {
                        item.disabled = true;
                    }
                });
            }

            if (categoryRow) {
                // replace view taxonomy link data.
                const viewTaxonomyLink = document.querySelector('.rdba-view-taxonomy-link');
                let viewTaxonomyLinkValue = viewTaxonomyLink?.href;
                viewTaxonomyLinkValue = viewTaxonomyLinkValue.replace('%tid%', categoryRow.tid);
                viewTaxonomyLinkValue = viewTaxonomyLinkValue.replace('%t_type%', categoryRow.t_type);
                viewTaxonomyLink.href = viewTaxonomyLinkValue;
            }// endif; categoryRow
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
        });
    }// ajaxGetFormData


    /**
     * Listen on form submit and ajax save data.
     * 
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

                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                thisForm.querySelector('.rdba-submit-button').setAttribute('disabled', 'disabled');

                let formData = new FormData(thisForm);

                RdbaCommon.XHR({
                    'url': RdbCMSACategoriesIndexObject.editCategoryRESTUrlBase + '/' + document.getElementById('tid').value,
                    'method': RdbCMSACategoriesIndexObject.editCategoryRESTMethod,
                    'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'data': new URLSearchParams(_.toArray(formData)).toString(),
                    'dataType': 'json'
                })
                .catch(function(responseObject) {
                    // XHR failed.
                    let response = responseObject.response;
                    console.error('[rdbcmsa]: ', responseObject);

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
                            document.querySelector(thisClass.dialogIDSelector + ' [data-dismiss="dialog"]').click();
                            //window.location.reload();// use datatables reload instead.
                            new DataTable(thisClass.datatableIDSelector).ajax.reload(null, false);
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
     * Listen ajax get parent done and do following tasks.
     * 
     * - XHR get form data and set it to form fields.
     * 
     * @param {RdbCMSACategoriesCommonActions} commonActions  The common actions class.
     * @returns {undefined}
     */
    listenGetParentsDone(commonActions) {
        let thisClass = this;

        if (!document.querySelector(thisClass.formIDSelector)) {
            // if not in edit page.
            // stop! don't do anything here. this is for prevent double listener.
            return ;
        }

        document.addEventListener('rdbcmsa.categoriesediting.ajaxgetparents.done', function(event) {
            if (!document.querySelector(thisClass.formIDSelector)) {
                // if not in edit page.
                // stop! don't do anything here. this is for prevent duoble call to functions below.
                return ;
            }

            // XHR get form data.
            thisClass.ajaxGetFormData()
            .then(() => {
                // activate tinymce
                commonActions.activateContentEditor();
            });
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
        // listen on type name and convert to URL.
        rdbcmsaCategoriesCommonActionsClass.listenUrlToCorrectUrl();

        // listen ajax get parent done and do tasks.
        thisClass.listenGetParentsDone(rdbcmsaCategoriesCommonActionsClass);

        // list form submit save data.
        thisClass.listenFormSubmit();
   }// staticInit


}// RdbCMSACategoriesEditController


document.addEventListener('rdbcmsa.contents-categories.editing.newinit', function() {
    // listen on new assets loaded.
    // this will be working on js loaded via AJAX.
    // must use together with `document.addEventListener('DOMContentLoaded')`
    if (
        RdbaCommon.isset(() => event.detail.rdbaUrlNoDomain) && 
        event.detail.rdbaUrlNoDomain.includes('/edit') !== false
    ) {
        RdbCMSACategoriesEditController.staticInit();
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // equivalent to jQuery document ready.
    // this will be working on normal page load (non AJAX).
    RdbCMSACategoriesEditController.staticInit();
}, false);
//document.addEventListener('rdbcmsa.contents-categories.editing.reinit', function() {
    // listen on re-open ajax dialog (assets is already loaded before).
    // ===this is required when... user click edit > save > close dialog > click edit other > now it won't load if there is no this listener.===
    // NO NEED THIS due to it is already work via event listener in `listenGetParentsDone()` method.
    // just keep this to prevent add un-necessary code.
//});