/**
 * File browser folders functional.
 */


class RdbCMSAFilesFileBrowserFolders {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        this.folderListSelector = '#rdbcmsa-files-filter-folder';
    }// constructor


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        // load folders.
        RdbCMSAFilesFileBrowserFolders.reloadFolders();
    }// init


    /**
     * Reload folders or ajax get folders.
     * 
     * This will get folders and set results into select box.
     * 
     * This method was called from `init()`, `listenClickDialogSaveButton()`, `listenClickDeleteFolder()` methods and reload button.
     * 
     * @returns {undefined}
     */
    static reloadFolders() {
        let thisClass = new RdbCMSAFilesFileBrowserFolders();
        let folderListElement = document.querySelector(thisClass.folderListSelector);

        let queryString = RdbCMSAFilesCommonObject.csrfName + '=' + RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfName]
        + '&' + RdbCMSAFilesCommonObject.csrfValue + '=' + RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfValue]

        RdbaCommon.XHR({
            'url': RdbCMSAFilesCommonObject.getFoldersRESTUrl + '?' + queryString,
            'method': RdbCMSAFilesCommonObject.getFoldersRESTMethod,
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
                RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.reject(responseObject);
        })
        .then(function(responseObject) {
            // XHR success.
            let response = responseObject.response;

            // reload list.
            if (typeof(response.list) !== 'undefined' && folderListElement) {
                // reset folder list
                folderListElement.querySelectorAll('option:not(:first-child)').forEach(function(item, index) {
                    item.remove();
                });

                // render to html
                // @link http://jsfiddle.net/adamboduch/5yt6M/ Original source code.
                // @link http://www.boduch.ca/2014/03/recursive-list-building-with-handlebars.html Original source code.
                let source = document.getElementById('rdbcmsa-files-folders-listing-main').innerHTML;
                let template = Handlebars.compile(source);
                Handlebars.registerPartial('list', document.getElementById('rdbcmsa-files-folders-listing').innerHTML);
                Handlebars.registerHelper('repeat', function(number, string) {
                    number = (parseInt(number) + 1);
                    return string.repeat(number);
                });
                let html = template({'children': response.list});
                folderListElement.insertAdjacentHTML('beforeend', html);
            }

            if (typeof(response) !== 'undefined') {
                if (typeof(response.formResultMessage) !== 'undefined') {
                    RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                }
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
            }

            return Promise.resolve(responseObject);
        });
    }// reloadFolders


}// RdbCMSAFilesFileBrowserFolders


document.addEventListener('DOMContentLoaded', function() {
    let rdbcmsaFilesFileBrowserFoldersClass = new RdbCMSAFilesFileBrowserFolders();
    // init the class.
    rdbcmsaFilesFileBrowserFoldersClass.init();
}, false);