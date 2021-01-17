/**
 * Copy files from node_modules to assets folder.
 */


'use strict';


const {series, parallel, src, dest} = require('gulp');
const print = require('gulp-print').default;


/**
 * Copy Ace editor.
 */
function copyAce(cb) {
    const rename = require('gulp-rename');
    const mergeStream =   require('merge-stream');

    return mergeStream(
        src('node_modules/ace-builds/src-min-noconflict/**')
            .pipe(print())
            .pipe(dest('assets/vendor/ace-builds/')),
        src('node_modules/ace-builds/LICENSE*')
            .pipe(print())
            .pipe(rename('ace-builds-license.txt'))
            .pipe(dest('assets/vendor/ace-builds/'))
    );
}// copyAce


/**
 * Copy Diff.
 */
function copyDiff(cb) {
    const rename = require('gulp-rename');
    const mergeStream =   require('merge-stream');

    return mergeStream(
        src('node_modules/diff/dist/**')
            .pipe(print())
            .pipe(dest('assets/vendor/diff/')),
        src('node_modules/diff/LICENSE*')
            .pipe(print())
            .pipe(rename('diff-license.txt'))
            .pipe(dest('assets/vendor/diff/'))
    );
}// copyDiff


/**
 * Copy Diff2HTML.
 */
function copyDiff2Html(cb) {
    const rename = require('gulp-rename');
    const mergeStream =   require('merge-stream');

    return mergeStream(
        src('node_modules/diff2html/bundles/**')
            .pipe(print())
            .pipe(dest('assets/vendor/diff2html/')),
        src('node_modules/diff2html/LICENSE.md')
            .pipe(print())
            .pipe(dest('assets/vendor/diff2html/'))
    );
}// copyDiff2Html


/**
 * Copy tagify
 */
function copyTagify(cb) {
    const rename = require('gulp-rename');
    const mergeStream =   require('merge-stream');

    return mergeStream(
        src('node_modules/@yaireo/tagify/dist/**')
            .pipe(print())
            .pipe(dest('assets/vendor/tagify/')),
        src('node_modules/@yaireo/tagify/LICENSE*')
            .pipe(print())
            .pipe(dest('assets/vendor/tagify/'))
    );
}// copyTagify


/**
 * Copy tinymce
 */
function copyTinyMCE(cb) {
    const rename = require('gulp-rename');
    const mergeStream =   require('merge-stream');
    const uglify = require('gulp-uglify-es').default;

    return mergeStream(
        src('node_modules/tinymce/**')
            .pipe(print())
            .pipe(dest('assets/vendor/tinymce/')),
    );
}// copyTinyMCE


exports.copyNodeModules = parallel(
    copyAce,
    copyDiff,
    copyDiff2Html,
    copyTagify,
    copyTinyMCE,
);