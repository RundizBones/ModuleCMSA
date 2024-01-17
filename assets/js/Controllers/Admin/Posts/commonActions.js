/**
 * Common actions for add, edit post in different post types such as article, page, etc.
 */


class RdbCMSAPostsCommonActions {


    /**
     * Class constructor.
     */
    constructor(options) {
        if (typeof(options) === 'undefined') {
            options = {};
        }

        if (
            !RdbaCommon.isset(() => options.formIDSelector) || 
            (RdbaCommon.isset(() => options.formIDSelector) && _.isEmpty(options.formIDSelector))
        ) {
            options.formIDSelector = '#posts-add-form'
        }
        this.formIDSelector = options.formIDSelector;

        if (
            !RdbaCommon.isset(() => options.tTypeForCategory) || 
            (RdbaCommon.isset(() => options.tTypeForCategory) && _.isEmpty(options.tTypeForCategory))
        ) {
            options.tTypeForCategory = 'category';
        }
        this.tTypeForCategory = options.tTypeForCategory;

        if (
            !RdbaCommon.isset(() => options.tTypeForTag) || 
            (RdbaCommon.isset(() => options.tTypeForTag) && _.isEmpty(options.tTypeForTag))
        ) {
            options.tTypeForTag = 'tag';
        }
        this.tTypeForTag = options.tTypeForTag;

        if (
            !RdbaCommon.isset(() => options.editingObject)
        ) {
            if (typeof(RdbCMSAPostsAddObject) !== 'undefined') {
                options.editingObject = RdbCMSAPostsAddObject;
            } else if (typeof(RdbCMSAPostsEditObject) !== 'undefined') {
                options.editingObject = RdbCMSAPostsEditObject;
            }
        }
        this.editingObject = options.editingObject;

        this.editControllerClass;
        this.postCommonEditRevision;

        this.aceEditor;
        this.tagify;
        this.tinyMce;
    }// constructor


    /**
     * Activate content editor.
     * 
     * @link https://www.tiny.cloud/docs/configure/editor-appearance/#font_formats TinyMCE default fonts.
     * @link https://www.tiny.cloud/docs/plugins/help/#help_tabs TinyMCE default help tabs.
     * @link https://www.tiny.cloud/docs/configure/editor-appearance/#examplethetinymcedefaultmenuitems TinyMCE default menu items.
     * @link https://www.tiny.cloud/docs/general-configuration-guide/basic-setup/#defaulttoolbarcontrols TinyMCE default toolbar.
     * @link https://www.tiny.cloud/docs/mobile/ Mobile settings.
     * @link https://www.tiny.cloud/docs/plugins/codesample/#codesample_languages Code sample languages.
     * @link https://prismjs.com/#supported-languages Prism supported languages.
     * @param {string} editorSelector The content editor selector. 
     * @returns {undefined}
     */
    activateContentEditor(editorSelector = '#revision_body_value') {
        let editingObject = this.editingObject;

        let tinyMceDefaultConfig = {
            'selector': editorSelector,
            //'content_css': RdbCMSAPostsIndexObject.publicCssBaseUrl + '/Controllers/Admin/FinancialTemplates/variable-plugin.css',
            'convert_urls': false,
            'height': 400,
            'menu': {
                file: {title: 'Files', items: 'newdocument restoredraft | preview | print '},
                edit: {title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall | searchreplace'},
                view: {title: 'View', items: 'code | visualaid visualchars visualblocks | preview fullscreen'},
                insert: {title: 'Insert', items: 'image link media rdbcmsafilebrowser template codesample inserttable | charmap emoticons hr | pagebreak nonbreaking anchor | insertdatetime'},
                format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript codeformat | formats blockformats fontformats fontsizes align lineheight | forecolor backcolor | removeformat'},
                tools: {title: 'Tools', items: 'code wordcount'},
                table: {title: 'Table', items: 'inserttable | cell row column | tableprops deletetable'},
                help: {title: 'Help', items: 'help'}
            },
            'mobile': {
                'menubar': true
            },
            'plugins': 'advlist anchor autosave charmap code codesample emoticons fullscreen help hr image insertdatetime link lists media nonbreaking pagebreak paste preview searchreplace table visualblocks visualchars wordcount',
            'rdbcmsaEditingObject': editingObject,
            'toolbar': 'undo redo | styleselect | bold italic underline | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist | image rdbcmsafilebrowser',
            'toolbar_mode': 'sliding',

            // autosave plugins options.
            'autosave_ask_before_unload': true,
            'autosave_interval': '30s',

            // image plugins options.
            'image_advtab': true,
            'image_caption': true,
            'image_title': true,

            'codesample_languages': [
                { text: 'Apache Configuration', value: 'apacheconf' },
                { text: 'ASP.NET (C#)', value: 'aspnet' },
                { text: 'Bash', value: 'bash' },
                { text: 'BASIC', value: 'basic' },
                { text: 'Batch', value: 'batch' },
                { text: 'BBcode', value: 'bbcode' },
                { text: 'C', value: 'c' },
                { text: 'C#', value: 'csharp' },
                { text: 'C++', value: 'cpp' },
                { text: 'CoffeeScript', value: 'coffeescript' },
                { text: 'CSS', value: 'css' },
                { text: 'Diff', value: 'diff' },
                { text: 'F#', value: 'fsharp' },
                { text: 'Git', value: 'git' },
                { text: 'Go', value: 'go' },
                { text: 'Haml', value: 'haml' },
                { text: 'Handlebars', value: 'handlebars' },
                { text: 'HTML/XML', value: 'markup' },
                { text: 'Icon', value: 'icon' },
                { text: '.ignore', value: 'ignore' },
                { text: 'Ini', value: 'ini' },
                { text: 'Java', value: 'java' },
                { text: 'JavaScript', value: 'javascript' },
                { text: 'JSDoc', value: 'jsdoc' },
                { text: 'JSON', value: 'json' },
                { text: 'JSONP', value: 'jsonp' },
                { text: 'Less', value: 'less' },
                { text: 'Makefile', value: 'makefile' },
                { text: 'Markdown', value: 'markdown' },
                { text: 'MongoDB', value: 'mongodb' },
                { text: 'nginx', value: 'nginx' },
                { text: 'Pascal', value: 'pascal' },
                { text: 'Perl', value: 'perl' },
                { text: 'PHP', value: 'php' },
                { text: 'PowerShell', value: 'powershell' },
                { text: '.properties', value: 'properties' },
                { text: 'Python', value: 'python' },
                { text: 'R', value: 'r' },
                { text: 'Regex', value: 'regex' },
                { text: 'Ruby', value: 'ruby' },
                { text: 'Sass (Sass)', value: 'sass' },
                { text: 'Sass (Scss)', value: 'scss' },
                { text: 'Smarty', value: 'smarty' },
                { text: 'SQL', value: 'sql' },
                { text: 'Twig', value: 'twig' },
                { text: 'TypeScript', value: 'typescript' },
                { text: 'VB.Net', value: 'vbnet' },
                { text: 'Visual Basic', value: 'vb' },
                { text: 'WebAssembly', value: 'wasm' },
                { text: 'Wiki markup', value: 'wiki' },
                { text: 'YAML', value: 'yaml' },
                { text: 'Other', value: 'none' },
            ]
        };// tinyMceDefaultConfig

        let rdbcmsaFileBrowserPlugin = '';
        if (RdbaCommon.isset(() => editingObject.publicModuleUrl)) {
            rdbcmsaFileBrowserPlugin = editingObject.publicModuleUrl + '/assets/js/Controllers/Admin/Posts/tinymce/plugins/rdbcmsa-file-browser.js';
            let newTinyMceConfig = {
                'external_plugins': {
                    'rdbcmsafilebrowser': rdbcmsaFileBrowserPlugin
                }
            };
            tinyMceDefaultConfig = _.defaultsDeep(newTinyMceConfig, tinyMceDefaultConfig);
        }

        this.tinyMce = tinymce.init(tinyMceDefaultConfig);

        return this.tinyMce;
    }// activateContentEditor


    /**
     * Activate featured image browser.
     * 
     * @returns {undefined}
     */
    activateFeaturedImageBrowser() {
        let thisClass = this;

        window.addEventListener('message', this.featuredImageOnMessage, false);
        // @link https://stackoverflow.com/a/11986895/128761 Access class property use target attribute.
        window.postCommonClass = this;

        RDTADialog.init();

        document.addEventListener('click', function(event) {
            if (
                RdbaCommon.isset(() => event.currentTarget.activeElement.id) &&
                event.currentTarget.activeElement.id === 'post_feature_image-openbrowser'
            ) {
                let dialogImageBrowser = document.getElementById('dialog-image-browser');
                if (dialogImageBrowser) {
                    dialogImageBrowser.querySelector('.rd-dialog-body').innerHTML = '<iframe src="' + thisClass.editingObject.getFileBrowserUrl + '?featured-image=1" tabindex="-1">';
                }
            }// endif element id matched;
        });
    }// activateFeaturedImageBrowser


    /**
     * Activate `HTML` editor.
     * 
     * @param {string} editorSelector The ID of HTML editor (textarea).
     * @returns {undefined}
     */
    activateHtmlEditor(editorSelector = 'revision_head_value') {
        if (!document.getElementById(editorSelector + '-editor')) {
            let event = new Event('rdbcmsa.postsCommonActions.activateHtmlEditor.notfound');
            document.dispatchEvent(event, {'bubbles': true});

            console.info('the editor was not found. (' + editorSelector + '-editor)');
            return ;
        }

        let aceEditor = ace.edit(editorSelector + '-editor', {
            'maxLines': 90,
            'minLines': 10,
            'mode': 'ace/mode/html'
        });
        aceEditor.setTheme('ace/theme/monokai');

        // listen on change and set value back to textarea.
        aceEditor.session.on('change', function(delta) {
            document.getElementById(editorSelector).value = aceEditor.getValue();
        });

        // do not validate doctype. 
        // @link https://stackoverflow.com/a/39176259/128761 Original source code.
        aceEditor.session.on('changeAnnotation', function () {
            let annotations = aceEditor.session.getAnnotations();
            if (!_.isArray(annotations)) {
                annotations = [];
            }

            let len = annotations.length;
            let i = len;

            while (i--) {
                if (/doctype first\. Expected/.test(annotations[i].text)) {
                    annotations.splice(i, 1);
                } else if (/Unexpected End of file\. Expected/.test(annotations[i].text)) {
                    annotations.splice(i, 1);
                }
            }
            if (len > annotations.length) {
                aceEditor.session.setAnnotations(annotations);
            }
        });

        let event = new Event('rdbcmsa.postsCommonActions.activateHtmlEditor.found');
        document.dispatchEvent(event, {'bubbles': true});

        this.aceEditor = aceEditor;
    }// activateHtmlEditor


    /**
     * Activate tags editor.
     * 
     * @link https://github.com/yairEO/tagify Tagify document.
     * @returns {undefined}
     */
    activateTagsEditor() {
        let thisClass = this;
        let tagsElement = document.querySelector('#prog_tags');
        let RdbCMSAPostsEditingObject = this.editingObject;

        let allowAddNewTag = RdbCMSAPostsEditingObject.permissionAddTag;

        let tagify = new Tagify(tagsElement, {
            'editTags': false,
            'enforceWhitelist': !allowAddNewTag// use opposite of permission result. see tagify document.
        });

        tagify.on('add', function(e) {
            if (!RdbaCommon.isset(() => e.detail.data.tid)) {
                // if not found tid, it is add new tag.
                //console.log('this is new tag.', e.detail.data);
                e.detail.tag.style = '--tag-bg: #d7fdd7;';// light green.
            }
        });
        tagify.on('input', function(e) {
            tagify.settings.whitelist.length = 0; // reset current whitelist
            tagify.loading(true) // show the loader animation

            RdbaCommon.XHR({
                'url': RdbCMSAPostsEditingObject.getTagsRESTUrl + '?search[value]=' + e.detail.value + '&search[regex]=false',
                'method': RdbCMSAPostsEditingObject.getTagsRESTMethod,
                'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                'dataType': 'json'
            })
            .catch(function(responseObject) {
                // XHR failed.
                let response = responseObject.response;

                if (response && response.formResultMessage) {
                    RDTAAlertDialog.alert({
                        'text': response.formResultMessage,
                        'type': 'error'
                    });
                }

                tagify.dropdown.hide.call(tagify);

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbCMSAPostsEditingObject.csrfKeyPair = response.csrfKeyPair;
                }

                return Promise.reject(responseObject);
            })
            .then(function(responseObject) {
                // XHR success.
                let response = responseObject.response;

                if (response && response.listItems) {
                    // format result to make it work for tagify.
                    let whitelist = [];
                    let i = 0;
                    response.listItems.forEach(function(item, index) {
                        whitelist[i] = {
                            'value': RdbaCommon.unEscapeHtml(item.t_name),
                            'tid': item.tid
                        };
                        i++;
                    });

                    // replace tagify "whitelist" array values with new values
                    // and add back the ones already choses as Tags
                    tagify.settings.whitelist.push(...whitelist, ...tagify.value);

                    tagify
                        .loading(false)
                        // render the suggestions dropdown.
                        .dropdown.show.call(tagify, e.detail.value);
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbCMSAPostsEditingObject.csrfKeyPair = response.csrfKeyPair;
                }

                return Promise.resolve(responseObject);
            });
        });

        this.tagify = tagify;
    }// activateTagsEditor


    /**
     * AJAX get post related data such as categories, statuses.
     * 
     * This method return promise so, it can be use with `.then(function(responseObject) {})`.<br>
     * Example:<pre>
     * let PromiseAjaxGetRelatedData = postCommonClass.ajaxGetRelatedData();
     * PromiseAjaxGetRelatedData.then(function(responseObject) {
     *     let response = responseObject.response;
     *     // your code.
     * });
     * </pre>
     * 
     * @param {string} ajaxURL The AJAX URL. No query string.
     * @param {string} ajaxMethod The AJAX method.
     * @returns {Promise}
     */
    ajaxGetRelatedData(ajaxURL, ajaxMethod) {
        let thisClass = this;
        let promiseObj = RdbaCommon.XHR({
            'url': ajaxURL + '?t_type=' + thisClass.tTypeForCategory,
            'method': ajaxMethod,
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
                thisClass.editingObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.reject(responseObject);
        })
        .then(function(responseObject) {
            // XHR success.
            let response = responseObject.response;

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                thisClass.editingObject.csrfKeyPair = response.csrfKeyPair;
            }

            // write categories checkboxes.
            if (RdbaCommon.isset(() => response.categories.items)) {
                let sourceTemplate = document.getElementById('rdbcmsa-contents-categories-formfields');
                let source = '';
                if (sourceTemplate) {
                    source = sourceTemplate.innerHTML;
                } else {
                    console.warn('source template (#rdbcmsa-contents-categories-formfields) was not found.');
                }

                let template = Handlebars.compile(source);
                Handlebars.registerHelper('getMarginLeft', function (levelNumber) {
                    if (isNaN(levelNumber)) {
                        return 0;
                    }

                    levelNumber = (parseInt(levelNumber) - 1);
                    let baseLeftSpace = .813;
                    return (baseLeftSpace * levelNumber);
                });
                let rendered = template(response.categories.items);
                let categoriesPlaceholder = document.querySelector('#categories-form-fields .control-wrapper');
                if (categoriesPlaceholder) {
                    categoriesPlaceholder.innerHTML = rendered;
                } else {
                    console.warn('categories placeholder (#categoriesPlaceholder) was not found.');
                }
            }

            // write statuses options.
            if (RdbaCommon.isset(() => response.postStatuses)) {
                let optionString = '';
                response.postStatuses.forEach(function(item, index) {
                    optionString += '<option value="' + item.value + '">' + item.text + '</option>';
                });
                let post_status = document.querySelector('#post_status');
                if (post_status) {
                    post_status.innerHTML = optionString;
                }
            }

            return Promise.resolve(responseObject);
        });

        return promiseObj;
    }// ajaxGetRelatedData


    /**
     * Detect browser features and set (or hide) description.
     * 
     * @returns {undefined}
     */
    detectBrowserFeaturesAndSetDescription() {
        // detect datetime-local input type supported.
        let isDateTimeLocalSupported = function () {
            let input = document.createElement('input');
            let value = 'a';
            input.setAttribute('type', 'datetime-local');
            input.setAttribute('value', value);
            return (input.value !== value);
        };
        if (isDateTimeLocalSupported()) {
            // if datetime-local supported.
            // hide description.
            let post_publish_date = document.querySelector('#post_publish_date');
            if (post_publish_date) {
                post_publish_date.nextElementSibling.classList.add('rd-hidden');
            }
        }
    }// detectBrowserFeaturesAndSetDescription


    /**
     * Listen message from featured image.
     * 
     * This method is callback function from `activateFeaturedImageBrowser() method.
     * 
     * @param {object} event
     * @returns {undefined}
     */
    featuredImageOnMessage(event) {
        // @link https://stackoverflow.com/a/11986895/128761 Access class property use target attribute.
        let thisClass = event.currentTarget.postCommonClass;

        if (event && event.data && event.data.sender === 'rdbcmsasetfeaturedimage') {
            let posts = (RdbaCommon.isset(() => event.data.content) ? event.data.content : {});
            thisClass.renderFeaturedImage(posts);

            let dialogElement = document.getElementById('dialog-image-browser');
            if (dialogElement) {
                let closeButton = dialogElement.querySelector('.rd-dialog-close');
                if (closeButton) {
                    closeButton.focus();
                    closeButton.click();
                }
            }
        }
    }// featuredImageOnMessage


    /**
     * Listen click to remove featured image.
     * 
     * @returns {undefined}
     */
    listenClickRemoveFeaturedImage() {
        document.addEventListener('click', function(event) {
            if (
                (
                    RdbaCommon.isset(() => event.currentTarget.activeElement.id) &&
                    event.currentTarget.activeElement.id === 'post_feature_image-removebutton'
                ) ||
                (
                    RdbaCommon.isset(() => event.target.id) &&
                    event.target.id === 'post_feature_image-removebutton'
                )
            ) {
                let post_feature_image = document.getElementById('post_feature_image');
                if (post_feature_image) {
                    post_feature_image.value = '';
                }
                let previewContainer = document.getElementById('post_feature_image-preview');
                previewContainer.innerHTML = '';
            }
        });
    }// listenClickRemoveFeaturedImage


    /**
     * Listen enable revision and allow revision log to write.
     * 
     * @returns {undefined}
     */
    listenEnableRevision() {
        let thisClass = this;

        document.addEventListener('change', function(event) {
            if (
                RdbaCommon.isset(() => event.target.id) &&
                event.target.id === 'prog_enable_revision'
            ) {
                event.preventDefault();

                let thisCheckbox = event.target;
                let revision_log =document.querySelector('#revision_log');
                if (thisCheckbox.checked && thisCheckbox.checked === true) {
                    revision_log.disabled = false;
                    revision_log.dataset.markDisabled = false;
                } else {
                    revision_log.disabled = true;
                    revision_log.dataset.markDisabled = true;
                }
            }
        });
    }// listenEnableRevision


    /**
     * Listen form submit and make it XHR.
     * 
     * @param {string} ajaxURL The AJAX URL. No query string.
     * @param {string} ajaxMethod The AJAX method.
     * @returns {undefined}
     */
    listenFormSubmit(ajaxURL, ajaxMethod) {
        let thisClass = this;

        document.addEventListener('submit', function(event) {
            if (
                RdbaCommon.isset(() => event.target.id) &&
                '#' + event.target.id === thisClass.formIDSelector
            ) {
                event.preventDefault();

                // form validations. ---------------------------------
                let post_name = document.querySelector('#post_name');
                if (!post_name || _.isEmpty(post_name.value)) {
                    RDTAAlertDialog.alert({
                        'text': thisClass.editingObject.txtPleaseEnterTitle,
                        'type': 'error'
                    });
                    return ;
                }
                // end form validations. ----------------------------

                // ajax save post.
                let thisForm = event.target;
                let saveButtons = thisForm.querySelectorAll('.rdbcmsa-contents-posts-save-container button,'
                    + ' .rdbcmsa-contents-posts-save-container input[type="submit"],'
                    + ' .rdbcmsa-contents-posts-save-container input[type="button"]'
                );

                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock save buttons
                saveButtons.forEach(function(item, index) {
                    item.disabled = true;
                });

                let formData = new FormData(thisForm);
                formData.append(thisClass.editingObject.csrfName, thisClass.editingObject.csrfKeyPair[thisClass.editingObject.csrfName]);
                formData.append(thisClass.editingObject.csrfValue, thisClass.editingObject.csrfKeyPair[thisClass.editingObject.csrfValue]);
                if (event.submitter && event.submitter.name && event.submitter.value) {
                    formData.append(event.submitter.name, event.submitter.value);
                }
                // check if there is translation matcher to add then set the source id. ----------
                const urlParams = new URLSearchParams(window.location.search);
                const fromPostId = urlParams.get('translation-matcher-from-post_id');
                if (!isNaN(fromPostId) && !isNaN(parseFloat(fromPostId))) {
                    formData.append('translation-matcher-from-post_id', fromPostId);
                }
                // end check if there is translation matcher to add then set the source id. ------

                RdbaCommon.XHR({
                    'url': ajaxURL,
                    'method': ajaxMethod,
                    'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'data': new URLSearchParams(_.toArray(formData)).toString(),
                    'dataType': 'json'
                })
                .catch(function(responseObject) {
                    // XHR failed.
                    let response = responseObject.response;

                    if (response && response.formResultMessage) {
                        RDTAAlertDialog.alert({
                            'type': RdbaCommon.getAlertClassFromStatus(response.formResultStatus),
                            'html': RdbaCommon.renderAlertContent(response.formResultMessage)
                        });
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        thisClass.editingObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    if (response.redirectBack) {
                        window.location.href = response.redirectBack;
                    } else {
                        // if anything else (no redirectBack).
                        if (
                            response.revision_id && response.revision_id > 0 &&
                            RdbaCommon.isset(() => thisClass.postCommonEditRevision.currentRevisionId)
                        ) {
                            thisClass.postCommonEditRevision.currentRevisionId = response.revision_id;
                        }
                        // save and stay.
                        thisClass.reloadFormData();
                    }

                    if (response && response.formResultMessage) {
                        // if there is form result message, display it.
                        RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        thisClass.editingObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.resolve(responseObject);
                })
                .finally(function() {
                    // remove loading icon
                    thisForm.querySelector('.loading-icon').remove();
                    // unlock save buttons
                    saveButtons.forEach(function(item, index) {
                        item.disabled = false;
                    });
                });
            }
        });
    }// listenFormSubmit


    /**
     * Listen post status change and do following.
     * 
     * Make published date input box read only/editable.<br>
     * Set preview text of current status to the submit button.
     * 
     * @param {string} postStatusId The post status input ID. 
     * @param {string} postPublishDateId The post publish date input ID. 
     * @returns {undefined}
     */
    listenPostStatusChange(postStatusId = 'post_status', postPublishDateId = 'post_publish_date') {
        let thisClass = this;

        document.addEventListener('change', function(event) {
            if (
                RdbaCommon.isset(() => event.target.id) &&
                event.target.id === postStatusId
            ) {
                event.preventDefault();

                let post_status = event.target;
                let post_publish_date = document.getElementById(postPublishDateId);

                if (post_status && post_status.value == 2) {
                    // if selected status is scheduled0
                    post_publish_date.readOnly = false;
                    post_publish_date.dataset.markDisabled = false;
                    post_publish_date.disabled = false;
                } else {
                    post_publish_date.readOnly = true;
                    post_publish_date.dataset.markDisabled = true;
                    post_publish_date.disabled = true;
                }

                if (RdbaCommon.isset(() => post_status.options[post_status.selectedIndex])) {
                    // set current status text to submit button.
                    let submitBtnStatus = document.getElementById('prog-current-post_status');
                    let selectedText = post_status.options[post_status.selectedIndex].text;
                    submitBtnStatus.innerHTML = ' <i>(' + selectedText + ')</i>';
                }
            }
        });
    }// listenPostStatusChange


    /**
     * Listen on type URL and correct to safe URL string.
     * 
     * @returns {undefined}
     */
    listenUrlToCorrectUrl() {
        let thisClass = this;

        document.addEventListener('keyup', function(event) {
            if (
                event.target &&
                event.target.id === 'alias_url' &&
                event.target.form &&
                '#' + event.target.form.id === thisClass.formIDSelector
            ) {
                let thisForm = event.target.form;
                let submitButton = thisForm.querySelector('.rdba-submit-button');
                submitButton.disabled = true;
            }
        });

        document.addEventListener('keyup', RdsUtils.delay(function(event) {
            if (
                event.target &&
                event.target.id === 'alias_url' &&
                event.target.form &&
                '#' + event.target.form.id === thisClass.formIDSelector
            ) {
                let thisForm = event.target.form;
                let urlField = event.target;
                let submitButton = thisForm.querySelector('.rdba-submit-button');

                let safeUrl = RdsUtils.convertUrlSafeString(urlField.value);

                if (urlField) {
                    urlField.value = safeUrl;
                }

                submitButton.disabled = false;
            }
        }, 700));
    }// listenUrlToCorrectUrl


    /**
     * Reload form data from latest post and its revision.
     * 
     * @returns {undefined}
     */
    reloadFormData() {
        let thisClass = this;
        let thisForm = document.querySelector(this.formIDSelector);
        if (thisForm) {
            // reset form.
            thisForm.reset();
        }

        if (RdbaCommon.isset(() => this.aceEditor)) {
            // if html code editor is in used.
            // destroy ace editor.
            this.aceEditor.destroy();
        }
        if (RdbaCommon.isset(() => this.tagify)) {
            // if tag editor is in used.
            // destroy tagify.
            this.tagify.destroy();
        }

        // ajax get latest form data and set form values.
        thisClass.editControllerClass.ajaxGetFormData()
        .then(function() {
            return thisClass.triggerEvents();
        })
        .then(function() {
            // reload editing tinyMCE.
            let tinyMCEBodyValue = tinymce.get('revision_body_value');
            if (tinyMCEBodyValue) {
                tinyMCEBodyValue.setContent(thisForm.querySelector('#revision_body_value').value);
                tinyMCEBodyValue.setDirty(false);
                tinyMCEBodyValue.fire('change');// trigger event.
            }
        })
        .then(function() {
            if (RdbaCommon.isset(() => thisClass.aceEditor)) {
                // if html code editor is in used.
                // reload ace editor by destroy and re-activate it. cannot find the way to update content properly.
                thisClass.activateHtmlEditor();
            }
            if (RdbaCommon.isset(() => thisClass.tagify)) {
                // if tag editor is in used.
                // re-activate tagify.
                thisClass.activateTagsEditor();
                thisClass.editControllerClass.setupFormTags(thisClass);
            }
        })
        .then(function() {
            // reload revision history.
            if (
                RdbaCommon.isset(() => thisClass.postCommonEditRevision.activatedDatatable) &&
                thisClass.postCommonEditRevision.activatedDatatable === true
            ) {
                thisClass.postCommonEditRevision.reloadDatatable();
            }
        });
    }// reloadFormData


    /**
     * Render featured image.
     * 
     * This method was called from `featuredImageOnMessage()` and other methods.
     * @param {Object} posts
     * @returns {undefined}
     */
    renderFeaturedImage(posts) {
        if (RdbaCommon.isset(() => posts.files.file_id)) {
            // render preview featured image template.
            let source = document.getElementById('rdbcmsa-featured-image-preview-elements').innerHTML;
            let template = Handlebars.compile(source);
            document.getElementById('post_feature_image-preview').innerHTML = template(posts);

            // set featured image value (file_id).
            document.getElementById('post_feature_image').value = posts.files.file_id;
        }// endif; posts.files.file_id
    }// renderFeaturedImage


    /**
     * Trigger form fields events.
     * 
     * @returns {Promise} Return promise object.
     */
    triggerEvents() {
        let promiseObj = new Promise((resolve, reject) => {
            // trigger event on elements.
            let post_status = document.getElementById('post_status');
            if (post_status) {
                post_status.dispatchEvent(new Event('change', {'bubbles': true}));
            }

            let prog_enable_revision = document.getElementById('prog_enable_revision');
            if (prog_enable_revision) {
                prog_enable_revision.dispatchEvent(new Event('change', {'bubbles': true}));
            }

            resolve();
        });

        return promiseObj;
    }// triggerEvents


}// RdbCMSAPostsCommonActions