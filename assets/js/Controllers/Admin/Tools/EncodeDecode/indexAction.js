/* 
 * Encode/Decode JS for its controller.
 */


class RdbCMSAToolsEncodeDecodeIndexController {


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        this.listenOnFormSubmit();
    }// init


    /**
     * Listen on form submit and make ajax request.
     * 
     * @returns {undefined}
     */
    listenOnFormSubmit() {
        let thisClass = this;
        let thisForm = document.getElementById('rdbcmsa-encodedecode-form');

        if (thisForm) {
            thisForm.addEventListener('submit', function(event) {
                event.preventDefault();

                let submitBtn = thisForm.querySelector('button[type="submit"]');

                // add spinner icon
                thisForm.querySelector('.submit-button-row .control-wrapper').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
                // lock submit button
                submitBtn.disabled = true;

                let formData = new FormData(thisForm);
                formData.append(RdbcmsaToolsEncodeDecodeIndexActionObject.csrfName, RdbcmsaToolsEncodeDecodeIndexActionObject.csrfKeyPair[RdbcmsaToolsEncodeDecodeIndexActionObject.csrfName]);
                formData.append(RdbcmsaToolsEncodeDecodeIndexActionObject.csrfValue, RdbcmsaToolsEncodeDecodeIndexActionObject.csrfKeyPair[RdbcmsaToolsEncodeDecodeIndexActionObject.csrfValue]);

                RdbaCommon.XHR({
                    'url': thisForm.action,
                    'method': thisForm.method,
                    'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'data': new URLSearchParams(_.toArray(formData)).toString(),
                    'dataType': 'json'
                })
                .catch(function(responseObject) {
                    // XHR failed.
                    let response = responseObject.response;

                    if (response && response.formResultMessage) {
                        RDTAAlertDialog.alert({
                            'text': response.formResultMessage,
                            'type': response.formResultStatus
                        });
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbcmsaToolsEncodeDecodeIndexActionObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;

                    // display result.
                    if (response && response.result) {
                        let resultHtml = '<pre>' + RdbaCommon.escapeHtml(response.result) + '</pre>';
                        document.getElementById('rdbcmsa-encodedecode-result').innerHTML = resultHtml;
                    } else {
                        document.getElementById('rdbcmsa-encodedecode-result').innerHTML = '';
                    }

                    if (typeof(response) !== 'undefined') {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbcmsaToolsEncodeDecodeIndexActionObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.resolve(responseObject);
                })
                .finally(function() {
                    // remove loading icon
                    thisForm.querySelector('.loading-icon').remove();
                    // unlock submit button
                    submitBtn.disabled = false;
                });
            });
        }
    }// listenOnFormSubmit


}// RdbCMSAToolsEncodeDecodeIndexController


document.addEventListener('DOMContentLoaded', function() {
    let rdbCMSAToolsEncodeDecodeIndexControllerClass = new RdbCMSAToolsEncodeDecodeIndexController();

    rdbCMSAToolsEncodeDecodeIndexControllerClass.init();
});