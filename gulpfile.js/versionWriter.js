/**
 * Version writer.
 * 
 * Write updated version to PHP file.
 */


'use strict';


const {dest, series, src} = require('gulp');
const fs = require('fs');
const mergeStream =   require('merge-stream');
const rename = require('gulp-rename');
const replace = require('gulp-replace');


/**
 * Write packages version to PHP file.
 *
 * Do not use too many `.pipe()` or it will have an error like this.
 * > MaxListenersExceededWarning: Possible EventEmitter memory leak detected. 11 end listeners added to [DestroyableTransform]. Use emitter.setMaxListeners() to increase limit
 */
function writePackagesVersionSet1(cb) {
    let packageJson = JSON.parse(fs.readFileSync('./package.json'));
    let packageDependencies = (typeof(packageJson.dependencies) !== 'undefined' ? packageJson.dependencies : {});

    let aceVersion = packageDependencies['ace-builds'];
    let bigNumberJsVesion = packageDependencies['bignumber.js'];
    let tagifyVersion = packageDependencies['@yaireo/tagify'];

    packageJson = undefined;
    packageDependencies = undefined;

    let phpFile = './ModuleData/ModuleAssets.php';
    let targetFolder = './ModuleData/';
    let tasks = [];

    // make backup
    let date = new Date();
    let timeStampInMs = date.getFullYear() + ('0' + (date.getMonth() + 1)).slice(-2) + ('0' + date.getDate()).slice(-2)
        + '_' + ('0' + date.getHours()).slice(-2) + ('0' + date.getMinutes()).slice(-2) + ('0' + date.getSeconds()).slice(-2)
        + '_' + date.getMilliseconds();
    tasks[0] = src(phpFile)
        .pipe(rename('ModuleAssets.backup' + timeStampInMs + '.php'))
        .pipe(dest('.backup/ModuleData/'));

    tasks[1] = src(phpFile);

    if (typeof(aceVersion) !== 'undefined') {
        aceVersion = getVersionNumberOnly(aceVersion);
        let regExp1 = new RegExp(getRegexPattern('ace-builds'), 'gi');
        tasks[1].pipe(replace(regExp1, '$1$2$3' + aceVersion + '$5$6'));
        aceVersion = undefined;
    }

    if (typeof(tagifyVersion) !== 'undefined') {
        tagifyVersion = getVersionNumberOnly(tagifyVersion);
        let regExp1 = new RegExp(getRegexPattern('tagify'), 'gi');
        tasks[1].pipe(replace(regExp1, '$1$2$3' + tagifyVersion + '$5$6'));
        tagifyVersion = undefined;
    }

    tasks[1].pipe(dest(targetFolder));

    return mergeStream(tasks);
}// writePackagesVersionSet1


/**
 * Write packages version to PHP file.
 *
 * Do not use too many `.pipe()` or it will have an error like this.
 * > MaxListenersExceededWarning: Possible EventEmitter memory leak detected. 11 end listeners added to [DestroyableTransform]. Use emitter.setMaxListeners() to increase limit
 */
function writePackagesVersionSet2(cb) {
    let packageJson = JSON.parse(fs.readFileSync('./package.json'));
    let packageDependencies = (typeof(packageJson.dependencies) !== 'undefined' ? packageJson.dependencies : {});

    let diff2HtmlVersion = packageDependencies['diff2html'];
    let jsDiffVersion = packageDependencies['diff'];
    let tinyMCEVersion = packageDependencies['tinymce'];

    packageJson = undefined;
    packageDependencies = undefined;

    let phpFile = './ModuleData/ModuleAssets.php';
    let targetFolder = './ModuleData/';
    let tasks = [];

    tasks[0] = src(phpFile);

    if (typeof(diff2HtmlVersion) !== 'undefined') {
        diff2HtmlVersion = getVersionNumberOnly(diff2HtmlVersion);
        let regExp1 = new RegExp(getRegexPattern('diff2html'), 'gi');
        tasks[0].pipe(replace(regExp1, '$1$2$3' + diff2HtmlVersion + '$5$6'));
        let regExp2 = new RegExp(getRegexPattern('diff2html-ui'), 'gi');
        tasks[0].pipe(replace(regExp2, '$1$2$3' + diff2HtmlVersion + '$5$6'));
        diff2HtmlVersion = undefined;
    }

    if (typeof(jsDiffVersion) !== 'undefined') {
        jsDiffVersion = getVersionNumberOnly(jsDiffVersion);
        let regExp1 = new RegExp(getRegexPattern('jsdiff'), 'gi');
        tasks[0].pipe(replace(regExp1, '$1$2$3' + jsDiffVersion + '$5$6'));
        jsDiffVersion = undefined;
    }

    if (typeof(tinyMCEVersion) !== 'undefined') {
        tinyMCEVersion = getVersionNumberOnly(tinyMCEVersion);
        let regExp1 = new RegExp(getRegexPattern('tinymce'), 'gi');
        tasks[0].pipe(replace(regExp1, '$1$2$3' + tinyMCEVersion + '$5$6'));
        tinyMCEVersion = undefined;
    }

    tasks[0].pipe(dest(targetFolder));

    return mergeStream(tasks);
}// writePackagesVersionSet2


/**
 * Get regular expression pattern.
 * 
 * @param {string} handleName
 * @returns {string}
 */
function getRegexPattern(handleName) {
    return '(\\['
        + '\\s*)'// group1
        + '([\\\'"]handle[\\\'"]\\s*=>\\s*[\\\'"]' + handleName + '[\\\'"],*\\s*)'// group2, 'handle' => 'xxx'
        + '([\\S\\s]+?version[\\\'"]\\s*=>\\s*[\\\'"])'// group3, anything and follow with quote or double quotes
        + '(.+)'// group4, version number
        + '([\\\'"],*)'// group5, end quote or double quotes
        + '(\\s*'
        + '\\])';// group6
}// getRegexPattern


/**
 * Get version number only (x.x.x).
 * 
 * Remove anything that is not dot, numbers.
 * 
 * @param {string} versionString
 * @returns {string}
 */
function getVersionNumberOnly(versionString) {
    return versionString.replace(/[^0-9.\-a-z]/ig, '');
}// getVersionNumberOnly


exports.writeVersions = series(
    writePackagesVersionSet1,
    writePackagesVersionSet2
);