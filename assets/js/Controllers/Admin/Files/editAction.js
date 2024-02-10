/**
 * Edit file dialog. - edit page JS for its controller.
 */


class RdbCMSAFilesEditController {


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
            options.formIDSelector = '#files-edit-form';
        }
        this.formIDSelector = options.formIDSelector;

        if (
            !RdbaCommon.isset(() => options.dialogIDSelector) || 
            (RdbaCommon.isset(() => options.dialogIDSelector) && _.isEmpty(options.dialogIDSelector))
        ) {
            options.dialogIDSelector = '#rdbcmsa-files-editing-dialog';
        }
        this.dialogIDSelector = options.dialogIDSelector;

        if (
            !RdbaCommon.isset(() => options.datatableIDSelector) || 
            (RdbaCommon.isset(() => options.datatableIDSelector) && _.isEmpty(options.datatableIDSelector))
        ) {
            options.datatableIDSelector = '#filesListItemsTable';
        }
        this.datatableIDSelector = options.datatableIDSelector;

        this.tinyMCE;
    }// constructor


    /**
     * Activate content editor.
     * 
     * The code here was copied from Modules/RdbCMSA/assets/js/Controllers/Admin/Posts/commonActions.js
     * 
     * @private This method was called from `ajaxGetFormData()`.
     * @param {string} editorSelector
     * @returns {undefined}
     */
    activateContentEditor(editorSelector = '#file_media_description') {
        if (typeof(tinymce) === 'undefined') {
            return ;
        }

        let tinyMceDefaultConfig = {
            'selector': editorSelector,
            'convert_urls': false,
            'height': 400,
            'menu': {
                file: {title: 'Files', items: 'newdocument restoredraft | preview | print '},
                edit: {title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall | searchreplace'},
                view: {title: 'View', items: 'code | visualaid visualchars visualblocks | preview fullscreen'},
                insert: {title: 'Insert', items: 'image link media rdbcmsafilebrowser template codesample inserttable | charmap emoticons hr | pagebreak nonbreaking anchor toc | insertdatetime'},
                format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript codeformat | formats blockformats fontformats fontsizes align lineheight | forecolor backcolor | removeformat'},
                tools: {title: 'Tools', items: 'code wordcount'},
                table: {title: 'Table', items: 'inserttable | cell row column | tableprops deletetable'},
                help: {title: 'Help', items: 'help'}
            },
            'mobile': {
                'menubar': true
            },
            'plugins': 'advlist anchor autosave charmap code codesample emoticons fullscreen help hr image insertdatetime link lists media nonbreaking pagebreak paste preview searchreplace table toc visualblocks visualchars wordcount',
            'toolbar': 'undo redo | styleselect | bold italic underline | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist | image rdbcmsafilebrowser',
            'toolbar_drawer': 'sliding',

            // autosave plugins options.
            'autosave_ask_before_unload': true,
            'autosave_interval': '30s',

            // image plugins options.
            'image_advtab': true,
            'image_caption': true,
            'image_title': true
        };// tinyMceDefaultConfig

        this.tinyMCE = tinymce.init(tinyMceDefaultConfig);

        return this.tinyMCE;
    }// activateContentEditor


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
        let formId = thisForm.querySelector('#file_id');

        RdbaCommon.XHR({
            'url': RdbCMSAFilesCommonObject.getFileRESTUrlBase + '/' + (formId ? formId.value : ''),
            'method': RdbCMSAFilesCommonObject.getFileRESTMethod,
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
                RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
                if (typeof(response.csrfName) !== 'undefined' && typeof(response.csrfValue) !== 'undefined') {
                    thisForm.querySelector('#rdba-form-csrf-name').value = response.csrfKeyPair[response.csrfName];
                    thisForm.querySelector('#rdba-form-csrf-value').value = response.csrfKeyPair[response.csrfValue];
                }
            }

            if (resultRow) {
                // render media (image, video, audio).
                thisClass.ajaxGetFormRenderMedia(resultRow);

                // set the data that have got via ajax to form fields.
                for (let prop in resultRow) {
                    if (
                        Object.prototype.hasOwnProperty.call(resultRow, prop) && 
                        document.getElementById(prop) && 
                        prop !== 'file_id' &&
                        prop !== 'file_status' &&
                        resultRow[prop] !== null
                    ) {
                        if (prop === 'file_media_description') {
                            // if description column, this field in html is allowed for HTML then don't unescape or the HTML elements will be messed with textarea.
                            document.getElementById(prop).value = resultRow[prop];
                        } else {
                            document.getElementById(prop).value = RdbaCommon.unEscapeHtml(resultRow[prop]);
                        }
                    }
                }// endfor;

                if (resultRow.file_status && resultRow.file_status === '1') {
                    document.getElementById('file_status').checked = true;
                } else {
                    document.getElementById('file_status').checked = false;
                }

                // render file metadata such as link to download, video or image width and height, etc.
                thisClass.ajaxGetFormRenderMetadata(resultRow);

                // activate content editor.
                thisClass.activateContentEditor('#file_media_description');
            }// endif resultRow
        });
    }// ajaxGetFormData


    /**
     * Render file metadata such as link to download, video or image width and height, etc.
     * 
     * @private This method was called from `ajaxGetFormData()` method.
     * @param {object} resultRow
     * @returns {undefined}
     */
    ajaxGetFormRenderMetadata(resultRow) {
        let fileMetadataPlaceholder = document.getElementById('files-metadata-row');
        let source = document.getElementById('files-metadata-row-template').innerHTML;
        resultRow.RdbCMSAFilesCommonObject = RdbCMSAFilesCommonObject;
        let template = Handlebars.compile(source);
        // @link https://stackoverflow.com/questions/42245693/handlebars-js-replacing-portion-of-string replace original source code.
        Handlebars.registerHelper('replace', function (find, replace, options) {
            let string = options.fn(this);
            return string.replace(find, replace);
        });
        
        let rendered = template(resultRow);

        if (fileMetadataPlaceholder) {
            fileMetadataPlaceholder.innerHTML = rendered;
        }
    }// ajaxGetFormRenderMetadata


    /**
     * Render media such as image, video, audio.
     * 
     * @private This method was called from `ajaxGetFormData()` method.
     * @param {object} resultRow
     * @returns {undefined}
     */
    ajaxGetFormRenderMedia(resultRow) {
        if (resultRow.file_visibility === '1') {
            // if in public/rdbadmin-public folder.
            let mediaViewsPlaceholder = document.getElementById('files-media-views-row');

            // display thumbnail. -------------------------
            if (RdbCMSAFilesCommonObject.imageExtensions.includes(resultRow.file_ext.toLowerCase())) {
                let thumbnailUrl = null;
                let publicUrlWithFolderPrefix;

                publicUrlWithFolderPrefix = RdbCMSAFilesCommonObject.rootPublicUrl + '/' + RdbCMSAFilesCommonObject.rootPublicFolderName;
                if (!_.isEmpty(resultRow.file_folder)) {
                    publicUrlWithFolderPrefix += '/' + resultRow.file_folder;
                }

                if (RdbaCommon.isset(() => resultRow.thumbnails.thumb600)) {
                    thumbnailUrl = resultRow.thumbnails.thumb600;
                } else if (RdbaCommon.isset(() => resultRow.thumbnails.thumb300)) {
                    thumbnailUrl = resultRow.thumbnails.thumb300;
                } else {
                    thumbnailUrl = publicUrlWithFolderPrefix + '/' + resultRow.file_name;
                }

                if (thumbnailUrl) {
                    if (mediaViewsPlaceholder) {
                        mediaViewsPlaceholder.innerHTML = '<a href="' + publicUrlWithFolderPrefix + '/' + resultRow.file_name + '" target="realImageFile">'
                            + '<img class="fluid" src="' + thumbnailUrl + '" alt="">'
                            + '</a>';
                    }
                }
            }
            // end display thumbnail. -------------------------

            // display video player. --------------------------
            if (
                RdbCMSAFilesCommonObject.videoExtensions.includes(resultRow.file_ext.toLowerCase()) && 
                resultRow.file_mime_type.toLowerCase().includes('video/')
            ) {
                let publicUrlWithFolderPrefix;

                publicUrlWithFolderPrefix = RdbCMSAFilesCommonObject.rootPublicUrl + '/' + RdbCMSAFilesCommonObject.rootPublicFolderName;
                if (!_.isEmpty(resultRow.file_folder)) {
                    publicUrlWithFolderPrefix += '/' + resultRow.file_folder;
                }

                if (mediaViewsPlaceholder) {
                    mediaViewsPlaceholder.innerHTML = '<div class="rd-embed-responsive rd-embed-responsive16by9">'
                        + '<video class="rd-embed-responsive-item" controls>'
                        + '<source src="' + publicUrlWithFolderPrefix + '/' + resultRow.file_name + '">'
                        + '</video>'
                        + '</div>';
                }
            }
            // end display video player. ---------------------

            // display audio player. --------------------------
            if (
                RdbCMSAFilesCommonObject.audioExtensions.includes(resultRow.file_ext.toLowerCase()) && 
                resultRow.file_mime_type.toLowerCase().includes('audio/')
            ) {
                let publicUrlWithFolderPrefix;

                publicUrlWithFolderPrefix = RdbCMSAFilesCommonObject.rootPublicUrl + '/' + RdbCMSAFilesCommonObject.rootPublicFolderName;
                if (!_.isEmpty(resultRow.file_folder)) {
                    publicUrlWithFolderPrefix += '/' + resultRow.file_folder;
                }

                if (mediaViewsPlaceholder) {
                    mediaViewsPlaceholder.innerHTML = '<audio class="rdcmsa-files-audio-media-views" controls>'
                        + '<source src="' + publicUrlWithFolderPrefix + '/' + resultRow.file_name + '">'
                        + '</audio>';
                }
            }
            // end display audio player. ---------------------
        }// endif; resultRow.file_visibility === '1'
    }// ajaxGetFormRenderMedia


    /**
     * Listen on form submit and make it XHR.
     * 
     * @private This method was called from `staticInit()` method.
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
                let formId = thisForm.querySelector('#file_id');

                // set csrf again to prevent firefox form cached.
                if (!RdbCMSAFilesCommonObject.isInDataTablesPage) {
                    thisForm.querySelector('#rdba-form-csrf-name').value = RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfName];
                    thisForm.querySelector('#rdba-form-csrf-value').value = RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfValue];
                }

                // reset form result placeholder
                thisForm.querySelector('.form-result-placeholder').innerHTML = '';
                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                submitBtn.disabled = true;

                let formData = new FormData(thisForm);

                RdbaCommon.XHR({
                    'url': RdbCMSAFilesCommonObject.editFileRESTUrlBase + '/' + (formId ? formId.value : ''),
                    'method': RdbCMSAFilesCommonObject.editFileRESTMethod,
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
                        RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
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
                        if (RdbCMSAFilesCommonObject && RdbCMSAFilesCommonObject.isInDataTablesPage && RdbCMSAFilesCommonObject.isInDataTablesPage === true) {
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
                        RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
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

        // ajax get form data.
        thisClass.ajaxGetFormData();
        // listen on form submit and make it AJAX request.
        thisClass.listenFormSubmit();
   }// staticInit


}// RdbCMSAFilesEditController


document.addEventListener('files.editing.newinit', function() {
    // listen on new assets loaded.
    // this will be working on js loaded via AJAX.
    // must use together with `document.addEventListener('DOMContentLoaded')`
    if (
        RdbaCommon.isset(() => event.detail.rdbaUrlNoDomain) && 
        event.detail.rdbaUrlNoDomain.includes('/edit') !== false
    ) {
        RdbCMSAFilesEditController.staticInit();
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // equivalent to jQuery document ready.
    // this will be working on normal page load (non AJAX).
    RdbCMSAFilesEditController.staticInit();
}, false);
document.addEventListener('files.editing.reinit', function() {
    // listen on re-open ajax dialog (assets is already loaded before).
    // this is required when... user click edit > save > close dialog > click edit other > now it won't load if there is no this listener.
    if (
        RdbaCommon.isset(() => event.detail.rdbaUrlNoDomain) && 
        event.detail.rdbaUrlNoDomain.includes('/edit') !== false
    ) {
        let rdbcmsaFilesEditControllerClass = new RdbCMSAFilesEditController();
        // ajax get form data.
        rdbcmsaFilesEditControllerClass.ajaxGetFormData();
    }
});