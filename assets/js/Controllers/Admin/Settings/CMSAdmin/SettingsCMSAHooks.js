/**
 * CMS admin settings for hooks into RdbAdmin module's settings page.
 * 
 * @since 0.0.6
 */


class SettingsCMSAHooks {


    /**
     * Ajax get form data.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    ajaxGetFormData() {
        let thisClass = this;
        let promiseObj = RdbaCommon.XHR({
            'url': RdbCMSASettingsCMSAObject.urls.getSettingsUrl,
            'method': RdbCMSASettingsCMSAObject.urls.getSettingsMethod,
            'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
            'dataType': 'json',
        })
        .then(function(responseObject) {
            let response = (responseObject ? responseObject.response : {});

            if (RdbaCommon.isset(() => response.configData) && _.isArray(response.configData)) {
                response.configData.forEach(function(item, index) {
                    if (
                        RdbaCommon.isset(() => item.config_name) && 
                        RdbaCommon.isset(() => item.config_value)
                    ) {
                        let thisInputElement = document.querySelector('#rdba-settings-form #' + item.config_name);
                        if (thisInputElement) {
                            if (thisInputElement.type.toLowerCase() === 'checkbox') {
                                if (thisInputElement.value == item.config_value) {
                                    thisInputElement.checked = true;
                                    //console.log('[rdbcmsa]: mark ' + key + ' as checked.');
                                }
                            } else if (thisInputElement.type.toLowerCase() !== 'file') {
                                thisInputElement.value = item.config_value;
                            }
                        }
                    }// endif isset item.xxx

                    if (RdbaCommon.isset(() => item.config_description)) {
                        let thisInputElement = document.querySelector('#rdba-settings-form #' + item.config_name);
                        let parentFormGroupElement = jQuery(thisInputElement).parents('.form-group')[0];
                        if (parentFormGroupElement) {
                            parentFormGroupElement.dataset.configdescription = RdbaCommon.escapeHtml(RdbaCommon.stripTags(item.config_description));
                        }
                    }// endif isset item.config_description
                });
            }// endif response.configData

            thisClass.displayWatermarkImage(response);

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbaSettings.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.resolve(responseObject);
        })
        .catch(function(responseObject) {
            console.error('[rdbcmsa]: ', responseObject);
            let response = (responseObject ? responseObject.response : {});

            if (typeof(response) !== 'undefined') {
                if (typeof(response.formResultMessage) !== 'undefined') {
                    let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                    let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                    document.querySelector('.form-result-placeholder').innerHTML = alertBox;
                }
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbaSettings.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.reject(responseObject);
        });

        return promiseObj;
    }// ajaxGetFormData


    /**
     * Display watermark image.
     * 
     * @private This method was called from `ajaxGetFormData()`, `listenFileUpload()`.
     * @param {object} response
     * @returns {undefined}
     */
    displayWatermarkImage(response) {
        if (
            RdbaCommon.isset(() => response.rdbcmsa_watermarkfile_base64) &&
            response.rdbcmsa_watermarkfile_base64 !== ''
        ) {
            // if there is watermark file.
            let reviewElement = document.querySelector('#current-watermark-review');
            if (reviewElement) {
                reviewElement.innerHTML = '<img class="fluid img-fluid rdbcmsa-watermark-review" src="' + response.rdbcmsa_watermarkfile_base64 + '" alt="">';
            }
            let prog_delete_watermark = document.querySelector('#prog_delete_watermarkLabel');
            if (prog_delete_watermark) {
                prog_delete_watermark.classList.remove('rd-hidden');
            }
        } else {
            // if there is NO watermark file.
            document.querySelector('#current-watermark-review').innerHTML = '';
            document.querySelector('#prog_delete_watermarkLabel').classList.add('rd-hidden');
            document.querySelector('#prog_delete_watermark').checked = false;
        }
    }// displayWatermarkImage


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        let thisClass = this;

        jQuery.when(uiXhrCommonData)
        .then(function() {
            return thisClass.ajaxGetFormData();
        })
        ;

        // Listen select file or drop file and start upload.
        this.listenFileUpload();
        // Listen form updated and get form value then re-render it again.
        this.listenFormUpdated();
    }// init


    /**
     * Listen form updated and get form value then re-render it again.
     * 
     * @returns {undefined}
     */
    listenFormUpdated() {
        let thisClass = this;

        document.addEventListener('rdbadmin.RdbaSettingsController.updated', (event) => {
            thisClass.ajaxGetFormData();
        }, false);
    }// listenFormUpdated


    /**
     * Listen select file or drop file and start upload.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenFileUpload() {
        let thisClass = this;
        let inputFileId = 'rdbcmsa_watermarkfile';
        let inputFileElement = document.querySelector('#' + inputFileId);

        if (inputFileElement) {
            let uploadStatusPlaceholder = document.getElementById('rdbcmsa-files-upload-status-placeholder');

            document.addEventListener('rdta.custominputfile.change', function(event) {
                event.preventDefault();

                inputFileElement = event.target;// force get new data.
                if (inputFileElement.getAttribute('id') !== inputFileId) {
                    // if not matched input file id for RdbCMSA watermark file.
                    // not working here.
                    return ;
                }

                if (inputFileElement.files.length > 1) {
                    // if too many files were selected.
                    // alert and stop working here.
                    RDTAAlertDialog.alert({
                        'type': 'error',
                        'text': RdbCMSASettingsCMSAObject.txtPleaseChooseOneFile
                    });
                    return ;
                }

                // add loading icon.
                uploadStatusPlaceholder.innerHTML = '&nbsp;<i class="fa-solid fa-spinner fa-pulse loading-icon"></i> ' + RdbCMSASettingsCMSAObject.txtUploading;

                let formData = new FormData();
                formData.append(RdbaSettings.csrfName, RdbaSettings.csrfKeyPair[RdbaSettings.csrfName]);
                formData.append(RdbaSettings.csrfValue, RdbaSettings.csrfKeyPair[RdbaSettings.csrfValue]);
                formData.append('rdbcmsa_watermarkfile', inputFileElement.files[0]);

                RdbaCommon.XHR({
                    'url': RdbCMSASettingsCMSAObject.urls.editUploadWatermarkUrl,
                    'method': RdbCMSASettingsCMSAObject.urls.editUploadWatermarkMethod,
                    //'contentType': 'multipart/form-data',// do not set `contentType` because it is already set in `formData`.
                    'data': formData,
                    'dataType': 'json',
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultStatus) !== 'undefined' && response.formResultStatus === 'warning') {
                            RDTAAlertDialog.alert({
                                'type': response.formResultStatus,
                                'text': response.formResultMessage
                            });
                        } else {
                            if (typeof(response.formResultMessage) !== 'undefined') {
                                RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                            }
                        }

                        if (typeof(response.uploadResult) !== 'undefined' && response.uploadResult === true) {
                            // if there is at least one file uploaded successfully.
                            // reset input file.
                            inputFileElement.value = '';
                        }

                        thisClass.displayWatermarkImage(response);
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaSettings.csrfKeyPair = response.csrfKeyPair;
                    }

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
                    } else {
                        if (RdbaCommon.isset(() => responseObject.status) && responseObject.status === 500) {
                            RDTAAlertDialog.alert({
                                'type': 'danger',
                                'text': 'Internal Server Error'
                            });
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbaSettings.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
                })
                .finally(function() {
                    // remove loading icon and upload status text.
                    uploadStatusPlaceholder.innerHTML = '';
                });
            });
        }
    }// listenFileUpload


}// SettingsCMSAHooks


document.addEventListener('DOMContentLoaded', function() {
    let settingsCMSAHooks = new SettingsCMSAHooks();

    // init the class.
    settingsCMSAHooks.init();
}, false);