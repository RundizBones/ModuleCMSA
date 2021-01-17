/**
 * File browser - files functional.
 */


class RdbCMSAFilesFileBrowserFiles {


    /**
     * Class constructor.
     * 
     * @param {object} options
     */
    constructor(options) {
        this.formIDSelector = '#rdbcmsa-files-list-form';

        this.localStorageNameFileBrowserOptions = 'fileBrowserOptions';

        // programmatic properties. do not change.
        this.isLoading = false;
        this.isEndRecords = false;
    }// constructor


    /**
     * Ajax load list of files to display and use.
     * 
     * @private This method was called from `init()`, `listenClickFilterButton()`, `listenScrollPagination()` methods.
     * @returns {undefined}
     */
    ajaxLoadFiles(start = 0) {
        let thisClass = this;
        if (isNaN(start)) {
            start = 0;
        }

        let thisForm = document.querySelector(thisClass.formIDSelector);
        let searchInput = thisForm.querySelector('#rdba-filter-search');
        let searchValue = '';
        if (searchInput) {
            searchValue = searchInput.value;
        }
        let filterFolder = thisForm.querySelector('#rdbcmsa-files-filter-folder');
        let filterFolderValue = '';
        if (filterFolder) {
            filterFolderValue = filterFolder.value;
        }
        let filterMime = thisForm.querySelector('#rdba-filter-mimetype');
        let filterMimeValue = '';
        if (filterMime) {
            filterMimeValue = filterMime.value;
        }
        let filterStatus = thisForm.querySelector('#rdbcmsa-files-filter-filestatus');
        let filterStatusValue = '';
        if (filterStatus) {
            filterStatusValue = filterStatus.value;
        }

        // generate querystring
        // copied from datatable.
        let queryString = '?columns[2][data]=file_id&columns[2][name]=&columns[2][searchable]=true&columns[2][orderable]=true&columns[2][search][value]=&columns[2][search][regex]=false';
        queryString += '&order[0][column]=2&order[0][dir]=desc&start=' + start + '&length=' + parseInt(RdbaUIXhrCommonData.configDb.rdbadmin_AdminItemsPerPage);
        queryString += '&search[value]=' + searchValue + '&search[regex]=false';
        queryString += '&filter-file_folder=' + filterFolderValue;
        queryString += '&filter-mimetype=' + filterMimeValue;
        queryString += '&filter-file_status=' + filterStatusValue;

        let promiseObj = RdbaCommon.XHR({
            'url': RdbCMSAFilesCommonObject.getFilesRESTUrl + queryString,
            'method': RdbCMSAFilesCommonObject.getFilesRESTMethod,
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

            if (typeof(response) !== 'undefined') {
                if (typeof(response.formResultMessage) !== 'undefined') {
                    RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                }
            }

            if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
            }

            console.log('loaded files list, needs call to render function.');

            return Promise.resolve(responseObject);
        });

        return promiseObj;
    }// ajaxLoadFiles


    /**
     * Render items after ajax load files.
     * 
     * @private This method was called from `init()`, `listenClickFilterButton()`, `listenScrollPagination()` methods that work after `ajaxLoadFiles()` method.
     * @param {object} responseObject
     * @returns {Promise}
     */
    ajaxLoadFilesRender(responseObject) {
        let response = responseObject.response;

        let promiseObj = new Promise((resolve, reject) => {
            if (typeof(response) !== 'undefined') {
                if (typeof(response.listItems) !== 'undefined') {
                    let source = document.getElementById('rdbcmsa-files-list-file-item-template').innerHTML;
                    let template = Handlebars.compile(source);
                    Handlebars.registerHelper('replace', function (find, replace, options) {
                        let string = options.fn(this);
                        return string.replace(find, replace);
                    });
                    Handlebars.registerHelper('isImage', function (file_ext, options) {
                        if (file_ext) {
                            file_ext = file_ext.toLowerCase();
                            return (RdbCMSAFilesCommonObject.imageExtensions.includes(file_ext) ? options.fn(this) : options.inverse(this));
                        }
                        return options.inverse(this);
                    });
                    Handlebars.registerHelper('getPublicUrlWithFolderPrefix', function(row, options) {
                        let publicUrlWithFolderPrefix = RdbCMSAFilesCommonObject.rootPublicUrl + '/' + RdbCMSAFilesCommonObject.rootPublicFolderName;
                        if (!_.isEmpty(row.file_folder)) {
                            publicUrlWithFolderPrefix += '/' + row.file_folder;
                        }
                        return publicUrlWithFolderPrefix;
                    });

                    response.RdbCMSAFilesCommonObject = RdbCMSAFilesCommonObject;

                    let templateResult = template(response);
                    let fileListPlaceholder = document.getElementById('rdbcmsa-files-list-placeholder');
                    if (fileListPlaceholder) {
                        fileListPlaceholder.insertAdjacentHTML('beforeend', templateResult);
                    }
                    console.log('render finish');
                    resolve('render finish');
                } else {
                    reject('no list items');
                }
            } else {
                reject('no response object');
            }
        });

        return promiseObj;
    }// ajaxLoadFilesRender


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        let thisClass = this;

        // listen file upload
        this.listenFileUpload();

        // listen click filter button
        this.listenClickFilterButton();
        // listen click insert button
        this.listenClickInsert();
        // listen enter and prevent form submit.
        this.listenKeyEnterPreventSubmit();

        // featured image functions.
        // listen click and force only one check box will be checked if it is featured image browser.
        this.listenClickSingleCheckbox();
        // listen click and set featured image.
        this.listenClickSetFeaturedImage();
        // listen click and set select images.
        this.listenClickSetSelectImages();

        // ajax load files.
        uiXhrCommonData
        .then(function() {
            thisClass.resetListPlaceholderAndPage();
            return thisClass.ajaxLoadFiles()
        })
        .then(function(response) {
            return thisClass.ajaxLoadFilesRender(response)
        })
        .then(function(status) {
            // listen scroll pagination
            thisClass.listenScrollPagination();
        });

        // restore file browser options.
        this.restoreOptions();
    }// init


    /**
     * Listen click on filter button and make ajax request.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenClickFilterButton() {
        let thisClass = this;

        document.addEventListener('click', function(event) {
            if (
                RdbaCommon.isset(() => event.target.closest('button').id) &&
                event.target.closest('button').id === 'rdba-datatables-filter-button'
            ) {
                event.preventDefault();

                // reset files list placeholder and page (pagination).
                thisClass.resetListPlaceholderAndPage();

                // reload files grid.
                thisClass.ajaxLoadFiles()
                .then(function(response) {
                    return thisClass.ajaxLoadFilesRender(response);
                });
            }
        }, false);
    }// listenClickFilterButton


    /**
     * Listen click insert button.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenClickInsert() {
        let thisClass = this;

        document.addEventListener('click', function(event) {
            if (
                RdbaCommon.isset(() => event.target.closest('button').id) &&
                event.target.closest('button').id === 'rdbcmsa-files-insert-button'
            ) {
                event.preventDefault();
                let thisForm = document.querySelector(thisClass.formIDSelector);

                // validate selected item.
                let formValidated = false;
                let inputCheckboxes = thisForm.querySelectorAll('.rdbcmsa-files-input-checkbox-fileid:checked');

                if (inputCheckboxes.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbCMSAFilesCommonObject.txtPleaseSelectAtLeastOne,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                if (formValidated === true) {
                    // remember options in the dialog for later use.
                    thisClass.listenClickInsertRememberOptions();
                    // prepare for insert.
                    let insertHtml = thisClass.listenClickInsertRenderHtml(inputCheckboxes);

                    // finish prepare insert html.
                    console.log('html to insert', insertHtml);
                    window.parent.postMessage({
                        mceAction: 'insertContent',
                        content: insertHtml,
                        sender: 'rdbcmsafilebrowser'
                    }, '*');
                }// endif; form validated
            }// endif; is click on insert button.
        });
    }// listenClickInsert


    /**
     * Determine image size should be use be detect that is there selected thumbnail URL or not.
     * 
     * @private This method was called from `listenClickInsertRenderHtml()` method.
     * @param {array} thumbnailSizesArrayReverse Thumbnails list as array sort by Descending.
     * @param {string} imageSize The selected image size value.
     * @param {object} eachInput Each input checkbox object that contain `.dataset` property.
     * @param {string} fileOriginalName File original name for use in debug.
     * @returns {string} Return determined image size should be use.
     */
    listenClickInsertDeterminImageSize(thumbnailSizesArrayReverse, imageSize, eachInput, fileOriginalName) {
        //console.log('determining image size for ' + fileOriginalName);
        let useImageSize = imageSize;
        if (useImageSize !== 'original') {
            // check for selected image size and get next one if not found or use original if really was not found.
            let datasetThumbnailName = 'thumbnails' + _.capitalize(imageSize);
            if (RdbaCommon.isset(() => eachInput.dataset[datasetThumbnailName])) {
                // if found selected thumbnail size URL.
                // no problem.
                //console.log('found thumbnail size URL (' + fileOriginalName + ' : ' + imageSize + ').');
            } else {
                // if not found selected thumbnail size URL.
                // get next smaller size.
                let searchIndexOfSelectedThumb = thumbnailSizesArrayReverse.indexOf(imageSize);
                if (searchIndexOfSelectedThumb !== -1) {
                    // if selected thumbnail size was found in thumbnails list.
                    let next = false;
                    for (let i = 0, n = thumbnailSizesArrayReverse.length; i < n; i++) {
                        if (parseInt(i) <= parseInt(searchIndexOfSelectedThumb)) {
                            // if this loop is before or same as searched one.
                            // do nothing for this loop.
                            continue;
                        }

                        let datasetThumbnailName = 'thumbnails' + _.capitalize(thumbnailSizesArrayReverse[i]);
                        if (RdbaCommon.isset(() => eachInput.dataset[datasetThumbnailName])) {
                            next = thumbnailSizesArrayReverse[i];
                            break;
                        }
                    }// endfor;

                    if (next !== false) {
                        useImageSize = next;
                    } else {
                        useImageSize = 'original';
                    }
                } else {
                    // if selected thumbnail size was NOT found in thumbnails list.
                    console.warn('The selected image size was not found in thumbnails list. (' + fileOriginalName + ' : ' + imageSize + ')');
                    console.log('The thumbnails list.', thumbnailSizesArrayReverse);
                    // use original.
                    useImageSize = 'original';
                }
            }// endif; eachInput.dataset.thumbnails-XXX
        }
        //console.log('determined, use this image size: ' + useImageSize);
        // end check for selected image size. ----

        return useImageSize;
    }// listenClickInsertDeterminImageSize


    /**
     * Determine link to from link to select box and file extension.
     * 
     * @private This method was called from `listenClickInsertRenderHtml()` method.
     * @param {object} linkTo The link to select box object that contain `.value` property.
     * @param {object} eachInput Each input checkbox object that contain `.dataset` property.
     * @returns {string} Return determined how link to will be use.
     */
    listenClickInsertDetermineLinkTo(linkTo, eachInput) {
        if (linkTo) {
            linkTo = linkTo.value;
        }

        let fileMimeType = '';
        let fileExt = '';
        if (eachInput) {
            if (RdbaCommon.isset(() => eachInput.dataset.file_mime_type)) {
                fileMimeType = eachInput.dataset.file_mime_type.toLowerCase();
            }
            if (RdbaCommon.isset(() => eachInput.dataset.file_ext)) {
                fileExt = eachInput.dataset.file_ext.toLowerCase();
            }
        }

        if (linkTo === 'file') {
            // if link to file.
            // file is image => link to file with display image.
            // file is audio, video =>link to file with display file name.
            // file is anything else =>link to file with display file name.
            // so, return as-is
            return linkTo;
        } else if (linkTo === 'attachment') {
            // if link to attachment page (front end view media page).
            // file is image => link to attachment page with display image.
            // file is audio, video =>link to attachment page with display file name.
            // file is anything else =>link to attachment page with display file name.
            // so, return as-is
            return linkTo;
        } else if (linkTo === 'embed') {
            // if link to is use embed for audio/video.
            // file is image => BOO! use link to attachment instead.
            // file is audio, video => display embedded media.
            // file is anything else => BOO! use link to attachment instead.
            if (fileMimeType.indexOf('audio/') !== -1 && RdbCMSAFilesCommonObject.audioExtensions.includes(fileExt)) {
                // if file is audio.
                return linkTo;
            } else if (fileMimeType.indexOf('video/') !== -1 && RdbCMSAFilesCommonObject.videoExtensions.includes(fileExt)) {
                // if file is video.
                return linkTo;
            } else {
                // if file is image or anything else.
                linkTo = 'attachment';
                return linkTo;
            }
        } else {
            // if link to is none (empty) or not link to.
            // file is image => just display image.
            // file is audio, video => just display file name.
            // file is anything else => just display file name.
            // so, return as-is
            return linkTo;
        }
    }// listenClickInsertDetermineLinkTo


    /**
     * Remember options in file browser dialog for easier on later use.
     * 
     * @private This method was called from `listenClickInsert()` method.
     * @returns {undefined}
     */
    listenClickInsertRememberOptions() {
        let linkTo = document.querySelector('#files-link-to');
        let imageSize = document.querySelector('#files-image-size');

        if (linkTo && imageSize) {
            let values = {
                'files-link-to': {
                    'value': linkTo.value
                },
                'files-image-size': {
                    'value': imageSize.value
                }
            };
            localStorage.setItem(this.localStorageNameFileBrowserOptions, JSON.stringify(values));
        }
    }// listenClickInsertRememberOptions


    /**
     * Render HTML from selected files.
     * 
     * @private This method was called from `listenClickInsert()` method.
     * @param {NodeList} inputCheckboxes The input checkboxes node list get from `.querySelectorAll()`.
     * @returns {string}
     */
    listenClickInsertRenderHtml(inputCheckboxes) {
        let insertHtml = '';

        if (inputCheckboxes instanceof NodeList) {
            let linkTo = document.querySelector('#files-link-to');
            let fullUrlToRootPublicStorage = document.querySelector('#full-url-to-rootpublic');
            if (fullUrlToRootPublicStorage) {
                fullUrlToRootPublicStorage = fullUrlToRootPublicStorage.value;
            }
            const thumbnailSizesAsArray = Object.keys(RdbCMSAFilesCommonObject.thumbnailSizes);
            let thumbnailSizesArrayReverse = thumbnailSizesAsArray;
            if (_.isArray(thumbnailSizesAsArray)) {
                thumbnailSizesAsArray.sort();
                thumbnailSizesArrayReverse = thumbnailSizesAsArray.reverse();
            }
            let imageSize = document.querySelector('#files-image-size');
            if (imageSize) {
                imageSize = imageSize.value;
            } else {
                imageSize = 'original';
            }

            for (let i = 0, n = inputCheckboxes.length; i < n; i++) {
                let eachInput = inputCheckboxes[i];

                if (eachInput && eachInput.dataset) {
                    let linkToValue = this.listenClickInsertDetermineLinkTo(linkTo, eachInput);
                    let eachFileMime = eachInput.dataset.file_mime_type;
                    if (eachFileMime) {
                        eachFileMime = eachFileMime.toLowerCase();
                    }
                    let eachFileExt = eachInput.dataset.file_ext;
                    if (eachFileExt) {
                        eachFileExt = eachFileExt.toLowerCase();
                    }
                    let fileFolder = eachInput.dataset.file_folder;
                    let fileName = eachInput.dataset.file_name;
                    let fileOriginalName = eachInput.dataset.file_original_name;

                    // generate link to. --------------------------
                    if (linkToValue === 'file' || linkToValue === 'attachment') {
                        // if choose link to file or attachment page.
                        insertHtml += '<a class="rdbcmsa-files-link" href="';
                        if (linkToValue === 'file') {
                            insertHtml += fullUrlToRootPublicStorage;
                            if (!_.isEmpty(fileFolder)) {
                                insertHtml += '/' + fileFolder;
                            }
                            insertHtml += '/' + fileName;
                        } else {
                            // if linkto is attachment.
                            // front-end files attachment url is here.
                            insertHtml += RdbCMSAFilesCommonObject.fullUrlToRoot + '/files/' + eachInput.value;
                        }
                        insertHtml += '">';
                    }
                    // end generate link to.-------------------------

                    // generate displaying file. --------------------
                    if (RdbCMSAFilesCommonObject.imageExtensions.includes(eachFileExt)) {
                        // if one of the selected files is an image.
                        let useImageSize = this.listenClickInsertDeterminImageSize(thumbnailSizesArrayReverse, imageSize, eachInput, fileOriginalName);
                        let imgSrc = '';
                        if (useImageSize.toLowerCase() === 'original') {
                            imgSrc = fullUrlToRootPublicStorage;
                            if (!_.isEmpty(fileFolder)) {
                                imgSrc += '/' + fileFolder;
                            }
                            imgSrc += '/' + fileName;
                        } else {
                            let datasetThumbnailName = 'thumbnails' + _.capitalize(useImageSize);
                            imgSrc = eachInput.dataset[datasetThumbnailName];
                        }

                        insertHtml += '<img'
                            + ' class="rdbcmsa-files rdbcmsa-files-image useImageSize-' + useImageSize + ' imageSize-' + imageSize + '"'
                            + ' src="' + imgSrc + '"'
                            + ' alt="' + RdbaCommon.escapeHtml(eachInput.dataset.file_media_name) + '"'
                            + '>';
                    } else if (eachFileMime.indexOf('audio/') !== -1 && RdbCMSAFilesCommonObject.audioExtensions.includes(eachFileExt)) {
                        // if one of the selected files is an audio.
                        if (linkTo.value === 'embed') {
                            // if choose link to embeded.
                            insertHtml += '<audio class="rdbcmsa-files rdbcmsa-files-audio-player" controls>';
                            insertHtml += '<source src="' + fullUrlToRootPublicStorage;
                            if (!_.isEmpty(fileFolder)) {
                                insertHtml += '/' + fileFolder;
                            }
                            insertHtml += '/' + fileName + '"';
                            insertHtml += '>';// close <source> tag.
                            insertHtml += fileOriginalName;
                            insertHtml += '</audio>';
                        } else {
                            // if not choose link to.
                            // display the original file name.
                            insertHtml += fileOriginalName;
                        }
                    } else if (eachFileMime.indexOf('video/') !== -1 && RdbCMSAFilesCommonObject.videoExtensions.includes(eachFileExt)) {
                        // if one of the selected files is an video.
                        if (linkTo.value === 'embed') {
                            // if choose link to embeded.
                            insertHtml += '<video class="rdbcmsa-files rdbcmsa-files-video-player" controls';
                            if (RdbaCommon.isset(() => eachInput.dataset.videoWidth)) {
                                insertHtml += ' width="' + eachInput.dataset.videoWidth + '"';
                            }
                            if (RdbaCommon.isset(() => eachInput.dataset.videoHeight)) {
                                insertHtml += ' height="' + eachInput.dataset.videoHeight + '"';
                            }
                            insertHtml += '>';// close <video> tag.
                            insertHtml += '<source src="' + fullUrlToRootPublicStorage;
                            if (!_.isEmpty(fileFolder)) {
                                insertHtml += '/' + fileFolder;
                            }
                            insertHtml += '/' + fileName + '"';
                            insertHtml += '>';// close <source> tag.
                            insertHtml += fileOriginalName;
                            insertHtml += '</video>';
                        } else {
                            // if not choose link to.
                            // display the original file name.
                            insertHtml += fileOriginalName;
                        }
                    } else {
                        // if anything else, treat as a file.
                        insertHtml += fileOriginalName;
                    }
                    // end generate displaying file. ---------------

                    // generate close link to or new line (html) in case no link. ----------
                    if (linkToValue === 'file' || linkToValue === 'attachment') {
                        insertHtml += '</a>';
                    } else if (linkToValue === '') {
                        insertHtml += '<br>';
                    }
                    // end generate close link to or new line (html). -----------------------

                    insertHtml += "\n";// new line for each of insert element.
                }// endif; eachInput
            }// endfor;
        }// endif; inputCheckboxes

        return insertHtml;
    }// listenClickInsertRenderHtml

    /**
     * Listen click and set featured image.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */

    listenClickSetFeaturedImage() {
        let thisClass = this;

        document.addEventListener('click', function(event) {
            if (RdbCMSAFilesCommonObject.featuredImage !== true) {
                // make sure that it is featured image browser, otherwise don't work to waste the resource.
                return ;
            }
            if (
                RdbaCommon.isset(() => event.target.closest('button').id) &&
                event.target.closest('button').id === 'rdbcmsa-files-setfeaturedimage-button'
            ) {
                event.preventDefault();
                let thisForm = document.querySelector(thisClass.formIDSelector);

                // validate selected item.
                let formValidated = false;
                let inputCheckboxes = thisForm.querySelectorAll('.rdbcmsa-files-input-checkbox-fileid:checked');

                if (inputCheckboxes.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbCMSAFilesCommonObject.txtPleaseSelectAtLeastOne,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                if (formValidated === true) {
                    let selectedContent = {};
                    let fullUrlToRootPublicStorage = document.querySelector('#full-url-to-rootpublic');
                    if (fullUrlToRootPublicStorage) {
                        fullUrlToRootPublicStorage = fullUrlToRootPublicStorage.value;
                    } else {
                        fullUrlToRootPublicStorage = '';
                    }

                    let selectedInput;
                    for (let i = 0, n = inputCheckboxes.length; i < n; i++) {
                        let eachInput = inputCheckboxes[i];
                        if (eachInput && eachInput.dataset) {
                            selectedInput = eachInput;
                            break;
                        }
                    }// endfor;
                    selectedContent.files = Object.assign({}, selectedInput.dataset);
                    selectedContent.files.file_id = selectedInput.value;

                    let thumbnailURL = thisClass.listenClickSetFeaturedImageGetThumbnail(selectedInput);
                    let originalURL = fullUrlToRootPublicStorage 
                        + (!_.isEmpty(selectedInput.dataset.file_folder) ? '/' + selectedInput.dataset.file_folder : '')
                        + '/' + selectedInput.dataset.file_name;
                    let urlsObject = {
                        'thumbnail': thumbnailURL,
                        'original': originalURL
                    };
                    selectedContent.files.urls = urlsObject;

                    window.parent.postMessage({
                        content: JSON.parse(JSON.stringify(selectedContent)),
                        sender: 'rdbcmsasetfeaturedimage'
                    }, '*');
                }// endif; form validated
            }// endif;
        });
    }// listenClickSetFeaturedImage


    /**
     * Get thumbnail URL from smallest to original.
     * 
     * @private This method was called from `listenClickSetFeaturedImage()`, `listenClickSetSelectImages()` methods.
     * @param {Object} selectedInput
     * @returns {String}
     */
    listenClickSetFeaturedImageGetThumbnail(selectedInput) {
        const thumbnailSizesAsArray = Object.keys(RdbCMSAFilesCommonObject.thumbnailSizes);
        let next = false;
        for (let i = 0, n = thumbnailSizesAsArray.length; i < n; i++) {
            let datasetThumbnailName = 'thumbnails' + _.capitalize(thumbnailSizesAsArray[i]);
            if (RdbaCommon.isset(() => selectedInput.dataset[datasetThumbnailName])) {
                next = thumbnailSizesAsArray[i];
                break;
            }
        }// endfor;

        let useImageSize;
        if (next !== false) {
            useImageSize = next;
        } else {
            useImageSize = 'original';
        }

        let fullUrlToRootPublicStorage = document.querySelector('#full-url-to-rootpublic');
        if (fullUrlToRootPublicStorage) {
            fullUrlToRootPublicStorage = fullUrlToRootPublicStorage.value;
        }

        let imgSrc = '';
        if (useImageSize.toLowerCase() === 'original') {
            imgSrc = fullUrlToRootPublicStorage;
            if (!_.isEmpty(selectedInput.dataset.file_folder)) {
                imgSrc += '/' + selectedInput.dataset.file_folder;
            }
            imgSrc += '/' + selectedInput.dataset.file_name;
        } else {
            let datasetThumbnailName = 'thumbnails' + _.capitalize(useImageSize);
            imgSrc = selectedInput.dataset[datasetThumbnailName];
        }

        return imgSrc;
    }// listenClickSetFeaturedImageGetThumbnail


    listenClickSetSelectImages() {
        let thisClass = this;

        document.addEventListener('click', function(event) {
            if (RdbCMSAFilesCommonObject.selectImages !== true) {
                // make sure that it is select images browser, otherwise don't work to waste the resource.
                return ;
            }
            if (
                RdbaCommon.isset(() => event.target.closest('button').id) &&
                event.target.closest('button').id === 'rdbcmsa-files-selectimages-button'
            ) {
                event.preventDefault();
                let thisForm = document.querySelector(thisClass.formIDSelector);

                // validate selected item.
                let formValidated = false;
                let inputCheckboxes = thisForm.querySelectorAll('.rdbcmsa-files-input-checkbox-fileid:checked');

                if (inputCheckboxes.length <= 0) {
                    RDTAAlertDialog.alert({
                        'text': RdbCMSAFilesCommonObject.txtPleaseSelectAtLeastOne,
                        'type': 'error'
                    });
                    formValidated = false;
                } else {
                    formValidated = true;
                }

                if (formValidated === true) {
                    let selectedContent = {};
                    let fullUrlToRootPublicStorage = document.querySelector('#full-url-to-rootpublic');
                    if (fullUrlToRootPublicStorage) {
                        fullUrlToRootPublicStorage = fullUrlToRootPublicStorage.value;
                    } else {
                        fullUrlToRootPublicStorage = '';
                    }

                    selectedContent.files = [];
                    for (let i = 0, n = inputCheckboxes.length; i < n; i++) {
                        let eachInput = inputCheckboxes[i];
                        if (eachInput && eachInput.dataset) {
                            let thumbnailURL = thisClass.listenClickSetFeaturedImageGetThumbnail(eachInput);
                            let originalURL = fullUrlToRootPublicStorage 
                                + (!_.isEmpty(eachInput.dataset.file_folder) ? '/' + eachInput.dataset.file_folder : '')
                                + '/' + eachInput.dataset.file_name;
                            let selectedInput = {
                                'dataset': eachInput.dataset,
                                'file_id': eachInput.value,
                                'urls': {
                                    'thumbnail': thumbnailURL,
                                    'original': originalURL
                                }
                            };
                            selectedContent.files.push(selectedInput);
                        }
                    }// endfor;

                    window.parent.postMessage({
                        content: JSON.parse(JSON.stringify(selectedContent)),
                        sender: 'rdbcmsasetselectimages'
                    }, '*');
                }// endif; form validated
            }// endif;
        });
    }// listenClickSetSelectImages


    /**
     * Listen click and force only one check box will be checked if it is featured image browser.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenClickSingleCheckbox() {
        if (RdbCMSAFilesCommonObject.featuredImage === true) {
            document.addEventListener('click', function(event) {
                if (RdbCMSAFilesCommonObject.featuredImage !== true) {
                    // check again to make very sure that it is featured image browser, otherwise don't work to waste the resource.
                    return ;
                }
                if (RdbaCommon.isset(() => event.target.classList) && event.target.classList.contains('rdbcmsa-files-input-checkbox-fileid')) {
                    let thisCheckbox = event.target;
                    let checkedBoxes = document.querySelectorAll('.rdbcmsa-files-input-checkbox-fileid:checked');
                    if (checkedBoxes) {
                        checkedBoxes.forEach(function(item, index) {
                            if (item !== thisCheckbox) {
                                item.checked = false;
                            }
                        });
                    }
                }
            });
        }
    }// listenClickSingleCheckbox


    /**
     * Listen select file or drop file and start upload.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenFileUpload() {
        let thisClass = this;
        let dropzoneId = 'rdbcmsa-files-dropzone';
        let inputFileId = 'files_inputfiles';
        let inputFileElement = document.querySelector('#' + inputFileId);

        // prevent drag & drop image file outside drop zone. --------------------------------------------------
        window.addEventListener('dragenter', function (e) {
            let thisTarget = e.target;
            let closestElement = null;
            if (typeof(thisTarget.tagName) !== 'undefined') {
                closestElement = thisTarget.closest('#' + dropzoneId);
            }
            if (closestElement === null && thisTarget.parentElement) {
                closestElement = thisTarget.parentElement.closest('#' + dropzoneId);
            }
            if (closestElement === '' || closestElement === null) {
                e.preventDefault();
                e.dataTransfer.effectAllowed = 'none';
                e.dataTransfer.dropEffect = 'none';
            } else {
                e.preventDefault();// prevent redirect page to show dropped image.
            }
        }, false);
        window.addEventListener('dragover', function (e) {
            let thisTarget = e.target;
            let closestElement = null;
            if (typeof(thisTarget.tagName) !== 'undefined') {
                closestElement = thisTarget.closest('#' + dropzoneId);
            }
            if (closestElement === null && thisTarget.parentElement) {
                closestElement = thisTarget.parentElement.closest('#' + dropzoneId);
            }
            if (closestElement === '' || closestElement === null) {
                e.preventDefault();
                e.dataTransfer.effectAllowed = 'none';
                e.dataTransfer.dropEffect = 'none';
            } else {
                e.preventDefault();// prevent redirect page to show dropped image.
            }
        });
        // end prevent drag & drop image file outside drop zone. ---------------------------------------------

        window.addEventListener('drop', function(event) {
            let thisTarget = event.target;
            let closestElement = null;
            if (typeof(thisTarget.tagName) !== 'undefined') {
                closestElement = thisTarget.closest('#' + dropzoneId);
            }
            if (closestElement === null && thisTarget.parentElement) {
                closestElement = thisTarget.parentElement.closest('#' + dropzoneId);
            }
            if (closestElement !== '' && closestElement !== null) {
                // if dropped in drop zone or input file.
                event.preventDefault();
                inputFileElement = document.querySelector('#' + inputFileId);// force get new data.
                inputFileElement.files = event.dataTransfer.files;
                //console.log('success set files to input file.', inputFileElement);
                inputFileElement.dispatchEvent(new Event('change', { 'bubbles': true }));
            } else {
                // if not dropped in drop zone and input file.
                event.preventDefault();
                //console.log('not in drop zone.');
                event.dataTransfer.effectAllowed = 'none';
                event.dataTransfer.dropEffect = 'none';
            }
        });

        if (inputFileElement) {
            let uploadStatusPlaceholder = document.getElementById('rdbcmsa-files-upload-status-placeholder');

            document.addEventListener('rdta.custominputfile.change', function(event) {
                event.preventDefault();

                inputFileElement = event.target;// force get new data.
                let selectedFolder = document.getElementById('rdbcmsa-files-filter-folder');

                // add loading icon.
                uploadStatusPlaceholder.innerHTML = '&nbsp;<i class="fas fa-spinner fa-pulse loading-icon"></i> ' + RdbCMSAFilesCommonObject.txtUploading;

                let formData = new FormData();
                formData.append(RdbCMSAFilesCommonObject.csrfName, RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfName]);
                formData.append(RdbCMSAFilesCommonObject.csrfValue, RdbCMSAFilesCommonObject.csrfKeyPair[RdbCMSAFilesCommonObject.csrfValue]);
                // append multiple files.
                // @link https://stackoverflow.com/a/14908250/128761 Original source code.
                let ins = inputFileElement.files.length;
                for (let x = 0; x < ins; x++) {
                    formData.append('files_inputfiles[]', inputFileElement.files[x]);
                }
                formData.append('filter-file_folder', (selectedFolder ? selectedFolder.value : ''));

                RdbaCommon.XHR({
                    'url': RdbCMSAFilesCommonObject.addFileRESTUrl,
                    'method': RdbCMSAFilesCommonObject.addFileRESTMethod,
                    //'contentType': 'multipart/form-data',// do not set `contentType` because it is already set in `formData`.
                    'data': formData,
                    'dataType': 'json',
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
                        RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.reject(responseObject);
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
                            // trigger filter files and ajax load
                            let filterButton = document.getElementById('rdba-datatables-filter-button');
                            if (filterButton) {
                                filterButton.focus();// focus to make current button work correctly.
                                filterButton.dispatchEvent(new Event('click', { 'bubbles': true }));
                            }
                        }
                    }

                    if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                        RdbCMSAFilesCommonObject.csrfKeyPair = response.csrfKeyPair;
                    }

                    return Promise.resolve(responseObject);
                })
                .finally(function() {
                    // remove loading icon and upload status text.
                    uploadStatusPlaceholder.innerHTML = '';
                });
            });
        }
    }// listenFileUpload


    /**
     * Listen on enter and prevent form submit.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenKeyEnterPreventSubmit() {
        document.addEventListener('keydown', function(event) {
            if (
                event.altKey === false &&
                event.ctrlKey === false &&
                event.shiftKey === false &&
                event.key.toLowerCase() === 'enter'
            ) {
                event.preventDefault();
                console.log('prevented form submit.');
            }
        });
    }// listenKeyEnterPreventSubmit


    /**
     * Listen on scroll down and then auto load pagination.
     * 
     * @private This method was called from `init()` method.
     * @returns {undefined}
     */
    listenScrollPagination() {
        let thisClass = this;
        let fileListPlaceholderSelector = '#rdbcmsa-files-list-placeholder';
        let fileListPlaceholder = document.querySelector(fileListPlaceholderSelector);

        if (fileListPlaceholder) {
            fileListPlaceholder.addEventListener('scroll', function(event) {
                let fileListPlaceholderFullHeight = parseInt(fileListPlaceholder.scrollHeight);
                let fileListPlaceholderScrollTop = parseInt(fileListPlaceholder.scrollTop);// top position of file list placeholder that was scrolled. start at 0.
                let fileListPlaceholderVisibleHeight = parseInt(fileListPlaceholder.offsetHeight);
                const paddingBottomSpaceToStartAjax = 100;

                if (
                    (fileListPlaceholderFullHeight - (fileListPlaceholderScrollTop + paddingBottomSpaceToStartAjax)) < fileListPlaceholderVisibleHeight &&
                    thisClass.isLoading === false &&
                    thisClass.isEndRecords === false
                ) {
                    let currentPage = document.getElementById('rdbcmsa-files-current-page');// start from 1.
                    let itemsPerPage = parseInt(RdbaUIXhrCommonData.configDb.rdbadmin_AdminItemsPerPage);
                    let nextPage = 2;
                    let nextStart = 20;
                    if (currentPage) {
                        nextPage = (parseInt(currentPage.value) + 1);
                        nextStart = ((nextPage - 1) * itemsPerPage);
                    }
                    console.log('scroll to bottom view, start ajax pagination at ' + nextStart + '.');

                    thisClass.isLoading = true;
                    thisClass.ajaxLoadFiles(nextStart)
                    .then(function(response) {
                        if (_.isEmpty(response.response.listItems)) {
                            thisClass.isEndRecords = true;
                        }

                        return thisClass.ajaxLoadFilesRender(response);
                    })
                    .then(function() {
                        // set new current page
                        currentPage.value = nextPage;
                        thisClass.isLoading = false;
                    });
                }// endif scroll into specific position
            }, false);
        }
    }// listenScrollPagination


    /**
     * Reset files list placeholder and page.
     * 
     * Also reset state of `isLoading` and `isEndRecords` properties of this class.
     * 
     * @private This method was called from `listenClickFilterButton()`, `int()` methods.
     * @returns {undefined}
     */
    resetListPlaceholderAndPage() {
        // clear result list
        let fileListPlaceholder = document.getElementById('rdbcmsa-files-list-placeholder');
        if (fileListPlaceholder) {
            fileListPlaceholder.innerHTML = '';
        }

        // reset current pagination page.
        let currentPage = document.getElementById('rdbcmsa-files-current-page');
        if (currentPage) {
            currentPage.value = 1;
        }

        this.isLoading = false;
        this.isEndRecords = false;
        console.log('`isLoading` and `isEndRecords` properties was reset.');
    }// resetListPlaceholderAndPage


    /**
     * Restore file browser options.
     * 
     * @private This method was called from `init()` mehtod.
     * @returns {undefined}
     */
    restoreOptions() {
        let optionsJsonString = localStorage.getItem(this.localStorageNameFileBrowserOptions);

        if (optionsJsonString) {
            let optionsJson = JSON.parse(optionsJsonString);
            for (const [key, item] of Object.entries(optionsJson)) {
                let thisOption = document.getElementById(key);
                if (thisOption && item) {
                    thisOption.value = item.value;
                }
            }
        }
    }// restoreOptions


}// RdbCMSAFilesFileBrowserFiles


document.addEventListener('DOMContentLoaded', function() {
    let rdbcmsaFilesFileBrowserFilesClass = new RdbCMSAFilesFileBrowserFiles();
    // init the class.
    rdbcmsaFilesFileBrowserFilesClass.init();
}, false);