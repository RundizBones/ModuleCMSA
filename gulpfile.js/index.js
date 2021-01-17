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

global.rdbPublicModuleAssetsDir = '../../public/Modules/RdbCMSA/assets';


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
 * Just echo out that file has been changed.
 * 
 * Can't get the file name right now.
 */
function watchFileChanged(cb) {
    console.log('File has been changed.');
    cb();
}// watchFileChanged


exports.default = series(
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
    watch('assets/**', {events: 'all'}, series(watchFileChanged, copyAssets.copyAssets))
};


exports.pack = series(
    pack.pack
);