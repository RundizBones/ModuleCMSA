/**
 * Main Gulp file.
 */


'use strict';


const {series, parallel, src, dest, watch} = require('gulp');
const fs = require('fs');
const copyNodeModules = require('./copyNodeModules');
const copyAssets = require('./copyAssets');
const pack = require('./pack');
const versionWriter = require('./versionWriter');
const path = require('path');

global.moduleAssetsDir = 'Modules/RdbCMSA/assets';
global.rdbPublicModuleAssetsDir = '../../public/' + moduleAssetsDir;


/**
 * Delete folders and files in it.
 * 
 * This will also call to `prepareDirs()` function to create folders.
 */
async function clean(cb) {
    const del = require('del');
    console.log('Cleaning path ' + path.resolve(rdbPublicModuleAssetsDir));
    await del(['assets/vendor']);
    await del([rdbPublicModuleAssetsDir], {force: true});
    cb();
}// clean


/**
 * Get `PUBLIC_PATH` php constant from command line and re-assign to global variable.
 * 
 * @since 0.0.6
 */
function getPublicPath(cb) {
    let exec = require('child_process').exec

    exec('php ../../rdb system:constants --name="PUBLIC_PATH"', (err, stdout, stderr) => {
        // the regular expression pattern of php constant has got from https://www.php.net/manual/en/language.constants.php
        const regex = /^([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)(\s+)[=](\s+)(.+)$/im;
        let m;

        if ((m = regex.exec(stdout)) !== null) {
            // The result can be accessed through the `m`-variable.
            if (typeof(m[4]) === 'string') {
                global.rdbPublicModuleAssetsDir = m[4] + '/' + moduleAssetsDir;
                console.log('re-assigned global.rdbPublicModuleAssetsDir: ', rdbPublicModuleAssetsDir);
            }
        }
        cb(err);
    });
}// getPublicPath


/**
 * Just echo out that file has been changed.
 * 
 * Can't get the file name right now.
 */
function watchFileChanged(cb) {
    console.log('File has been changed.');
    cb();
}// watchFileChanged


exports.default = series(
    getPublicPath,
    clean,
    parallel(
        copyNodeModules.copyNodeModules
    ),
    copyAssets.copyAssets
);


exports.writeVersions = series(
    versionWriter.writeVersions
);


exports.watch = function() {
    watch(
        'assets/**', {events: 'all'}, 
        series(
            getPublicPath,
            watchFileChanged, 
            copyAssets.copyAssets
        )
    )
};


exports.pack = series(
    pack.pack
);