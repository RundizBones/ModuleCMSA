/**
 * RundizBones CMS admin file browser plugin.
 * 
 * @link https://www.tiny.cloud/docs/ui-components/toolbarbuttons/ Add button reference.
 * @link https://www.tiny.cloud/docs/ui-components/urldialog/ URL dialog reference.
 */


tinymce.PluginManager.add('rdbcmsafilebrowser', function(editor) {
    let editingObject = {};
    if (typeof(editor.settings.rdbcmsaEditingObject) !== 'undefined') {
        editingObject = editor.settings.rdbcmsaEditingObject;
    }

    /**
     * On message event, insert the content to editor.
     * 
     * @param {object} event
     * @returns {undefined}
     */
    function rdbcmsaFileBrowserOnMessage(event) {
        if (event && event.data && event.data.sender === 'rdbcmsafilebrowser') {
            tinymce.activeEditor.insertContent(event.data.content);
            tinymce.activeEditor.windowManager.close();
            window.removeEventListener('message', rdbcmsaFileBrowserOnMessage, false);
        }
    }// rdbcmsaFileBrowserOnMessage


    /**
     * Open file browser in TinyMCE dialog (URL dialog).
     * 
     * @returns {undefined}
     */
    function rdbcmsaOpenFileBrowser() {
        window.addEventListener('message', rdbcmsaFileBrowserOnMessage, false);

        let winHeight = (window.innerHeight - 20);
        let winWidth = (window.innerWidth - 20);

        editor.windowManager.openUrl({
            title: editingObject.txtFileBrowser,
            url: editingObject.getFileBrowserUrl,
            height: winHeight,
            width: winWidth
        });
    }// rdbcmsaOpenFileBrowser


    // add TinyMCE buttons. -------------------------------
    editor.ui.registry.addButton('rdbcmsafilebrowser', {
        icon: 'browse',
        tooltip: editingObject.txtFileBrowser,
        onAction: rdbcmsaOpenFileBrowser
    });

    editor.ui.registry.addMenuItem('rdbcmsafilebrowser', {
        icon: 'browse',
        text: editingObject.txtFileBrowser,
        onAction: rdbcmsaOpenFileBrowser,
        context: 'insert'
    });
    // end add TinyMCE buttons. --------------------------
});// end tinymce.PluginManager.add