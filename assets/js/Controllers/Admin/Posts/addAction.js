/**
 * Add post JS for its controller.
 */


class RdbCMSAPostsAddController {


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
            options.formIDSelector = '#posts-add-form';
        }
        this.formIDSelector = options.formIDSelector;

        if (
            !RdbaCommon.isset(() => options.tTypeForCategory) || 
            (RdbaCommon.isset(() => options.tTypeForCategory) && _.isEmpty(options.tTypeForCategory))
        ) {
            options.tTypeForCategory = RdbCMSAPostsAddObject.tTypeForCategory;
        }
        this.tTypeForCategory = options.tTypeForCategory;

        if (
            !RdbaCommon.isset(() => options.tTypeForTag) || 
            (RdbaCommon.isset(() => options.tTypeForTag) && _.isEmpty(options.tTypeForTag))
        ) {
            options.tTypeForTag = RdbCMSAPostsAddObject.tTypeForTag;
        }
        this.tTypeForTag = options.tTypeForTag;

        this.postCommonClass;
    }// constructor


    /**
     * Prepare form and make it ready to use.
     * 
     * @private This method was called from `staticInit()` method.
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
        .then(function(response) {
            // set author data to form.
            if (RdbaCommon.isset(() => response.userData)) {
                document.querySelector('#user_id').value = response.userData.user_id;
                document.querySelector('#prog_display_author').value = response.userData.user_display_name + ' (' + response.userData.user_id + ')';
            }
        })
        .then(function() {
            // get post relared data such as categories, statuses and set to form.
            return thisClass.postCommonClass.ajaxGetRelatedData(
                RdbCMSAPostsAddObject.getPostRelatedDataRESTUrl, 
                RdbCMSAPostsAddObject.getPostRelatedDataRESTMethod
            );
        })
        .then(function() {
            // force setup form value(s).
            thisClass.setupFormValues();
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
                console.error(ex);
            }

            // activate tags editor.
            try {
                postCommonAction.activateTagsEditor();
            } catch (ex) {
                console.error(ex);
            }

            // activate featured image browser.
            try {
                postCommonAction.activateFeaturedImageBrowser();
            } catch (ex) {
                console.error(ex);
            }

            // activate body content editor.
            return postCommonAction.activateContentEditor();
        });
    }// setupForm


    /**
     * Force setup form value(s).
     * 
     * @private This method was called from `setupForm()` method.
     * @returns {undefined}
     */
    setupFormValues() {
        let post_status = document.getElementById('post_status');
        if (post_status) {
            post_status.value = 1;// 1 is published.
        }
    }// setupFormValues


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

        // activate tabs.
        RDTATabs.init('#posts-editing-tabs', {rememberLastTab: true});

        // listen form submit and make it XHR.
        postCommonClass.listenFormSubmit(
            RdbCMSAPostsAddObject.addPostRESTUrl,
            RdbCMSAPostsAddObject.addPostRESTMethod
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


}// RdbCMSAPostsAddController


document.addEventListener('DOMContentLoaded', function() {
    RdbCMSAPostsAddController.staticInit();
}, false);