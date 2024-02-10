/**
 * Edit post JS for its controller.
 */


class RdbCMSAPostsEditController {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        if (typeof(options) === 'undefined') {
            options = {};
        }

        // the overall tabs element ID selector.
        if (
            !RdbaCommon.isset(() => options.editingTabsSelector) ||
            (RdbaCommon.isset(() => options.editingTabsSelector) && _.isEmpty(options.editingTabsSelector))
        ) {
            options.editingTabsSelector = '#posts-editing-tabs';
        }
        this.editingTabsSelector = options.editingTabsSelector;

        // editing form ID selector.
        if (
            !RdbaCommon.isset(() => options.formIDSelector) || 
            (RdbaCommon.isset(() => options.formIDSelector) && _.isEmpty(options.formIDSelector))
        ) {
            options.formIDSelector = '#posts-add-form';
        }
        this.formIDSelector = options.formIDSelector;

        if (
            !RdbaCommon.isset(() => options.tTypeForCategory) || 
            (RdbaCommon.isset(() => options.tTypeForCategory) && _.isEmpty(options.tTypeForCategory))
        ) {
            options.tTypeForCategory = RdbCMSAPostsEditObject.tTypeForCategory;
        }
        this.tTypeForCategory = options.tTypeForCategory;

        if (
            !RdbaCommon.isset(() => options.tTypeForTag) || 
            (RdbaCommon.isset(() => options.tTypeForTag) && _.isEmpty(options.tTypeForTag))
        ) {
            options.tTypeForTag = RdbCMSAPostsEditObject.tTypeForTag;
        }
        this.tTypeForTag = options.tTypeForTag;

        this.ajaxGetFormDataPromise = jQuery.Deferred();// no native JS that can wait until another ajax resolve this.
        this.postCommonClass;
        this.postCommonEditRevision;
    }// constructor


    /**
     * Ajax get form data for edit.
     * 
     * @private This method was called from `setupForm()`, `listenRevisionRollback()` methods.
     * @returns {undefined}
     */
    ajaxGetFormData() {
        let thisClass = this;

        let promiseObj = RdbaCommon.XHR({
            'url': RdbCMSAPostsEditObject.getPostRESTUrlBase + '/' + RdbCMSAPostsEditObject.post_id,
            'method': RdbCMSAPostsEditObject.getPostRESTMethod,
            'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
            'dataType': 'json'
        })
        .then(function(responseObject) {
            // XHR success.
            let response = responseObject.response;
            let resultRow = response.result;

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbCMSAPostsEditObject.csrfKeyPair = response.csrfKeyPair;
            }

            if (resultRow.revision_id) {
                thisClass.postCommonEditRevision.currentRevisionId = resultRow.revision_id;
            }

            // set the data that have got via ajax to form fields.
            for (let prop in resultRow) {
                if (
                    Object.prototype.hasOwnProperty.call(resultRow, prop) && 
                    document.getElementById(prop) && 
                    prop !== 'post_id' &&
                    resultRow[prop] !== null
                ) {
                    if (prop === 'revision_body_value' || prop === 'revision_body_summary' || prop === 'revision_head_value') {
                        // if it is HTML allowed fields then don't unescape or the HTML elements will be messed with textarea.
                        document.getElementById(prop).value = resultRow[prop];
                    } else {
                        document.getElementById(prop).value = RdbaCommon.unEscapeHtml(resultRow[prop]);
                    }
                }
            }// endfor;

            thisClass.postCommonClass.renderFeaturedImage(resultRow);

            if (resultRow) {
                if (RdbaCommon.isset(() => resultRow.post_status) && resultRow.post_status === '5') {
                    // if status is trashed.
                    // lock form.
                    let submitBtn = document.querySelector('.rdba-submit-button');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.dataset.markDisabled = true;
                    }
                }

                // set post_fields.
                if (resultRow.postFields) {
                    for (const [key, item] of Object.entries(resultRow.postFields)) {
                        let formInput = document.getElementById('post_fields-' + key);
                        if (formInput) {
                            formInput.value = item.field_value;
                        }
                    }
                }

                // set categories fields.
                if (resultRow.categories && _.isArray(resultRow.categories)) {
                    resultRow.categories.forEach(function(item, index) {
                        let prog_categories = document.querySelectorAll('.prog_categories');
                        if (_.isArray(prog_categories) || _.isObject(prog_categories)) {
                            prog_categories.forEach(function(item2, index2) {
                                if (item2.value == item.tid) {
                                    item2.checked = true;
                                }
                            });
                        }
                    });
                }

                // set tags for add them later after activated tagify.
                if (typeof(resultRow.tags) !== 'undefined') {
                    thisClass.ajaxGetFormTags = resultRow.tags;
                }

                // set revision head value field.
                if (typeof(resultRow.revision_head_value) !== 'undefined') {
                    let revision_head_valueEditor = document.getElementById('revision_head_value-editor');
                    if (revision_head_valueEditor) {
                        revision_head_valueEditor.innerHTML = RdbaCommon.escapeHtml(resultRow.revision_head_value);
                    }
                }

                // set author display name.
                if (typeof(resultRow.user_display_name) !== 'undefined') {
                    let prog_display_author = document.getElementById('prog_display_author');
                    prog_display_author.value = RdbaCommon.escapeHtml(resultRow.user_display_name) + ' (' + resultRow.user_id + ')';
                }

                // re-format publish date/time.
                if (typeof(resultRow.post_publish_date_gmt) !== 'undefined' && !_.isEmpty(resultRow.post_publish_date_gmt)) {
                    let post_publish_date = document.getElementById('post_publish_date');

                    let siteTimezone;
                    if (RdbaCommon.isset(() => RdbaUIXhrCommonData.configDb.rdbadmin_SiteTimezone)) {
                        siteTimezone = RdbaUIXhrCommonData.configDb.rdbadmin_SiteTimezone;
                    } else {
                        siteTimezone = 'Asia/Bangkok';
                    }
                    post_publish_date.value = moment(resultRow.post_publish_date_gmt + 'Z').tz(siteTimezone).format('YYYY-M-DTHH:mm');
                }

                // empty revision log textarea. this field must always be empty for enter new log.
                let revision_log = document.getElementById('revision_log');
                if (revision_log) {
                    revision_log.value = '';
                }

                // hide revision history if there is just 0 or less.
                if (typeof(resultRow.total_revisions) !== 'undefined' && resultRow.total_revisions <= '0') {
                    // if total revision history is 0 or less.
                    let revisionHistoryTabNav = document.getElementById('post-tab-nav-revisionhistory');
                    revisionHistoryTabNav.classList.add('rd-hidden');
                    let revisionHistoryTabContent = document.getElementById('post-tab-revisionhistory');
                    revisionHistoryTabContent.classList.add('rd-hidden');

                    if (revisionHistoryTabNav.classList.contains('active')) {
                        // if current active tab is revision history.
                        console.log('[rdbcmsa]: revision history is empty, hide the revision history tab.');
                        let tabsNav = document.querySelector('.rd-tabs-nav');
                        tabsNav.firstElementChild.querySelector('a').click();
                    }
                }

                // replace view post link data.
                const viewPostLink = document.querySelector('.rdba-view-post-link');
                let viewPostLinkValue = viewPostLink?.href;
                viewPostLinkValue = viewPostLinkValue.replace('%post_id%', resultRow.post_id);
                viewPostLinkValue = viewPostLinkValue.replace('%post_type%', resultRow.post_type);
                viewPostLink.href = viewPostLinkValue;
            }// endif resultRow

            thisClass.ajaxGetFormDataPromise.resolve(responseObject);
            return Promise.resolve(responseObject);
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
                RdbCMSAPostsEditObject.csrfKeyPair = response.csrfKeyPair;
            }

            thisClass.ajaxGetFormDataPromise.reject(responseObject);
            return Promise.reject(responseObject);
        });

        return promiseObj;
    }// ajaxGetFormData


    /**
     * Prepare form and make it ready to use.
     * 
     * @private Thism method was called from `staticInit()` method.
     * @returns {undefined}
     */
    setupForm() {
        let thisClass = this;
        let thisForm = document.querySelector(this.formIDSelector);

        // disable form.
        let formElements = (thisForm ? thisForm.elements : []);
        for (var i = 0, len = formElements.length; i < len; ++i) {
            formElements[i].disabled = true;
        }// endfor;

        uiXhrCommonData
        .then(function() {
            // get post relared data such as categories, statuses and set to form.
            return thisClass.postCommonClass.ajaxGetRelatedData(
                RdbCMSAPostsEditObject.getPostRelatedDataRESTUrl, 
                RdbCMSAPostsEditObject.getPostRelatedDataRESTMethod
            );
        })
        .then(function(response) {
            // ajax get form data
            return thisClass.ajaxGetFormData();
        })
        .then(function() {
            // trigger events such as post status changed.
            return thisClass.postCommonClass.triggerEvents();
        })
        .then(function() {
            // detect browser features and set (or hide) description.
            thisClass.postCommonClass.detectBrowserFeaturesAndSetDescription();
        })
        .then(function() {
            // re-enable form
            for (var i = 0, len = formElements.length; i < len; ++i) {
                if (
                    !RdbaCommon.isset(() => formElements[i].dataset.markDisabled) || 
                    formElements[i].dataset.markDisabled == false
                ) {
                    formElements[i].disabled = false;
                }
            }// endfor;
        })
        .then(function() {
            let postCommonAction = thisClass.postCommonClass;

            // activate HTML (in head content) editor.
            try {
                postCommonAction.activateHtmlEditor();
                let revision_head_value = document.getElementById('revision_head_value');
                if (revision_head_value) {
                    revision_head_value.classList.add('rd-hidden');
                }
            } catch (ex) {
                console.error('[rdbcmsa]: ', ex);
            }

            // activate tags editor.
            try {
                postCommonAction.activateTagsEditor();
                thisClass.setupFormTags(postCommonAction);
            } catch (ex) {
                console.error('[rdbcmsa]: ', ex);
            }

            // activate featured image browser.
            try {
                postCommonAction.activateFeaturedImageBrowser();
            } catch (ex) {
                console.error('[rdbcmsa]: ', ex);
            }

            // activate body content editor.
            return postCommonAction.activateContentEditor();
        })
        ;
    }// setupForm


    /**
     * Setup form tag(s).
     * 
     * @private This method was called from `setupForm()`, `listenRevisionRollback()` methods.
     * @returns {undefined}
     */
    setupFormTags(postCommonAction) {
        if (typeof(this.ajaxGetFormTags) !== 'undefined' && typeof(postCommonAction.tagify) !== 'undefined') {
            // format result to make it work for tagify.
            let whitelist = [];
            let i = 0;
            this.ajaxGetFormTags.forEach(function(item, index) {
                whitelist[i] = {
                    'value': RdbaCommon.unEscapeHtml(item.t_name),
                    'tid': item.tid
                };
                i++;
            });
            postCommonAction.tagify.addTags(whitelist);
        }
    }// setupFormTags


    /**
     * Static initialize the class.
     * 
     * @returns {undefined}
     */
    static staticInit() {
        let thisClass = new this();
        let postCommonClass = new RdbCMSAPostsCommonActions({
            'formIDSelector': thisClass.formIDSelector,
            'tTypeForTag': thisClass.tTypeForTag,
            'tTypeForCategory': thisClass.tTypeForCategory
        });
        // set post common class for inherit usage.
        thisClass.postCommonClass = postCommonClass;

        // revision history functional.
        thisClass.postCommonEditRevision = new RdbCMSAPostsCommonEditRevision();
        thisClass.postCommonEditRevision.editControllerClass = thisClass;
        thisClass.postCommonEditRevision.postCommonClass = postCommonClass;
        // initiate the revision history class
        thisClass.postCommonEditRevision.init();

        // set common use classes for `postCommonActions` class.
        thisClass.postCommonClass.editControllerClass = thisClass;
        thisClass.postCommonClass.postCommonEditRevision = thisClass.postCommonEditRevision;

        // activate tabs.
        RDTATabs.init(thisClass.editingTabsSelector, {rememberLastTab: true});

        // listen form submit and make it XHR.
        postCommonClass.listenFormSubmit(
            RdbCMSAPostsEditObject.editPostRESTUrlBase + '/' + RdbCMSAPostsEditObject.post_id,
            RdbCMSAPostsEditObject.editPostRESTMethod
        );

        // listen post status change and make publish date read only or editable.
        postCommonClass.listenPostStatusChange();
        // listen enable revision and allow revision log to write.
        postCommonClass.listenEnableRevision();
        // listen click to remove featured image.
        postCommonClass.listenClickRemoveFeaturedImage();

        // setup form
        thisClass.setupForm();
        // listen on type URL and correct to safe URL string.
        postCommonClass.listenUrlToCorrectUrl();
    }// staticInit


}// RdbCMSAPostsEditController


document.addEventListener('DOMContentLoaded', function() {
    RdbCMSAPostsEditController.staticInit();
}, false);