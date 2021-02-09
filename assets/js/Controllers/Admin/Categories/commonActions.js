/**
 * Common actions for editing category.
 */


class RdbCMSACategoriesCommonActions {


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
    activateContentEditor(editorSelector = '#t_description') {
        console.log('activating tinymce.', tinymce.EditorManager.editors);

        let editingObject = {};
        if (typeof(RdbCMSACategoriesIndexObject) !== 'undefined') {
            editingObject = RdbCMSACategoriesIndexObject;
        }

        let tinyMceDefaultConfig = {
            'selector': editorSelector,
            //'content_css': RdbCMSAPostsIndexObject.publicCssBaseUrl + '/Controllers/Admin/FinancialTemplates/variable-plugin.css',
            'convert_urls': false,
            'height': 400,
            'menu': {
                file: {title: 'Files', items: 'newdocument restoredraft | preview | print '},
                edit: {title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall | searchreplace'},
                view: {title: 'View', items: 'code | visualaid visualchars visualblocks | preview fullscreen'},
                insert: {title: 'Insert', items: 'image link media rdbcmsafilebrowser template codesample inserttable | charmap emoticons hr | pagebreak nonbreaking anchor toc | insertdatetime'},
                format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript codeformat | formats blockformats fontformats fontsizes align | forecolor backcolor | removeformat'},
                tools: {title: 'Tools', items: 'code wordcount'},
                table: {title: 'Table', items: 'inserttable | cell row column | tableprops deletetable'},
                help: {title: 'Help', items: 'help'}
            },
            'mobile': {
                'menubar': true
            },
            'plugins': 'advlist anchor autosave charmap code codesample emoticons fullscreen help hr image insertdatetime link lists media nonbreaking pagebreak paste preview searchreplace table toc visualblocks visualchars wordcount',
            'rdbcmsaEditingObject': editingObject,
            'toolbar': 'undo redo | styleselect | bold italic underline | alignleft aligncenter alignright alignjustify | outdent indent | image rdbcmsafilebrowser',
            'toolbar_drawer': 'sliding',

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
            ],

            'setup': function(editor) {
                editor.on('change', function() {
                    // @link https://stackoverflow.com/a/2266945/128761 trigger save to make change back to textarea.
                    // @link https://stackoverflow.com/a/24284657/128761 an example how to use trigger save.
                    tinymce.triggerSave();
                });
            },// setup:
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
     * Ajax get parent categories.
     * 
     * This method was called from `listenOnChangeType()`, `listenDIalogOpened()` methods.
     * 
     * @returns {undefined}
     */
    ajaxGetParentCategories() {
        let editingForm = document.querySelector(this.formIDSelector);
        if (!editingForm) {
            console.log('due to this is working from common class, the editing form maybe duplicated call. Skipping editing form for ' + this.formIDSelector);
            return Promise.resolve();
        }
        let submitBtn = editingForm.querySelector('button[type="submit"]');
        let parentCategorySelectbox = editingForm.querySelector('#parent_id');
        // add spinner icon
        editingForm.querySelector('.action-status-placeholder').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
        // lock submit button.
        submitBtn.disabled = true;

        // ajax get data.
        let promiseObj = RdbaCommon.XHR({
            'url': RdbCMSACategoriesIndexObject.getCategoriesRESTUrl,
            'method': RdbCMSACategoriesIndexObject.getCategoriesRESTMethod,
            'dataType': 'json'
        })
        .catch(function(responseObject) {
            // XHR failed.
            let response = responseObject.response;

            if (response && response.formResultMessage) {
                RDTAAlertDialog.alert({
                    'html': RdbaCommon.renderAlertContent(response.formResultMessage),
                    'type': 'error'
                });
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbCMSACategoriesIndexObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.reject(responseObject);
        })
        .then(function(responseObject) {
            // XHR success.
            let response = responseObject.response;

            // remove parent options except first.
            let parentCategoryOptions = parentCategorySelectbox.querySelectorAll('option:not(:first-child)');
            if (!_.isEmpty(parentCategoryOptions)) {
                parentCategoryOptions.forEach(function(item, index) {
                    item.remove();
                });
            }

            // render parent category.
            if (RdbaCommon.isset(() => response.listItems) && _.isArray(response.listItems)) {
                let selectOptions = '';
                let indentText = ' &nbsp; &nbsp;';
                response.listItems.forEach(function(item, index) {
                    selectOptions += '<option value="' + item.tid + '"';
                    selectOptions += ' data-t_level="' + item.t_level + '"';
                    selectOptions += ' data-t_left="' + item.t_left + '"';
                    selectOptions += ' data-t_right="' + item.t_right + '"';
                    selectOptions += '>';
                    if (item.t_level > 1) {
                        selectOptions += indentText.repeat((item.t_level - 1));
                    }
                    selectOptions += item.t_name;
                    selectOptions += '</option>';
                });
                parentCategorySelectbox.insertAdjacentHTML('beforeend', selectOptions);
            }

            if (response && response.formResultMessage) {
                // if there is form result message, display it.
                RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbCMSACategoriesIndexObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.resolve(responseObject);
        })
        .finally(function() {
            // remove loading icon
            editingForm.querySelector('.loading-icon').remove();
            // unlock submit button
            submitBtn.disabled = false;
        });

        return promiseObj;
    }// ajaxGetParentCategories


    /**
     * Listen dialog closed and destroy tinymce.
     * 
     * @link https://www.tiny.cloud/docs/api/tinymce/tinymce.editor/ Reference.
     * @returns {undefined}
     */
    listenDialogClosed() {
        let dialogElement = document.querySelector(this.dialogIDSelector);

        if (dialogElement) {
            dialogElement.addEventListener('rdta.dialog.closed', function() {
                if (RdbaCommon.isset(() => tinymce.activeEditor) && tinymce.activeEditor !== null) {
                    // destroy tinymce to allow it re-init again.
                    console.log('destroy tinymce', tinymce.activeEditor.destroy());
                }
            });
        }
    }// listenDialogClosed


    /**
     * Listen on dialog opened and then ajax get parent categories.
     * 
     * Moved a call to `ajaxGetParentCategories()` in here because when it was re-opened the parent category select box is always empty.<br>
     * Moved it here make it request every dialog opened.
     * 
     * @returns {undefined}
     */
    listenDialogOpened() {
        let thisClass = this;
        let dialogElement = document.querySelector(this.dialogIDSelector);
        let editFormElement = document.querySelector(this.formIDSelector);

        if (!editFormElement) {
            // if not in editing form, add form. maybe in bulk actions confirmation page.
            // dont do anything here.
            return ;
        }

        if (dialogElement === null || dialogElement === '') {
            // if not in dialog but in editing page.
            thisClass.ajaxGetParentCategories()
                .then(function(response) {
                    if (typeof(response) !== 'undefined') {
                        let event = new Event('rdbcmsa.categoriesediting.ajaxgetparents.done');
                        document.dispatchEvent(event, {'bubbles': true});
                    }
                });
        } else {
            dialogElement.addEventListener('rdta.dialog.opened', function(event) {
                thisClass.ajaxGetParentCategories()
                    .then(function(response) {
                        if (typeof(response) !== 'undefined') {
                            let event = new Event('rdbcmsa.categoriesediting.ajaxgetparents.done');
                            document.dispatchEvent(event, {'bubbles': true});
                        }
                    });
            });
        }
    }// listenDialogOpened


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


}// RdbCMSACategoriesCommonActions