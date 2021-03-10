/**
 * Pack folders and files into zip that is ready to use in distribute release.
 */


'use strict';


const {series, parallel, src, dest} = require('gulp');
let argv = require('yargs').argv;
const fs = require('fs');
const print = require('gulp-print').default;
const zip = require('gulp-zip');


/**
 * Pack files for production or development zip.
 * 
 * To pack for development, run `gulp pack --development` or `npm run pack -- --development`.<br>
 * To pack for production, run `gulp pack` or `npm run pack`.
 * 
 * @param {type} cb
 * @returns {unresolved}
 */
function packDist(cb) {
    let installerPhpContent = fs.readFileSync('./Installer.php', 'utf-8');
    let regexPattern = /@version(\s?)(?<version>[\d\.]+)/miu;
    let matched = installerPhpContent.match(regexPattern);
    let moduleVersion = 'unknown';
    if (matched && matched.groups && matched.groups.version) {
        moduleVersion = matched.groups.version;
    }

    let isProduction = true;
    if (argv.development || argv.dev) {
        isProduction = false;
    }

    let targetDirs = [];
    if (isProduction === true) {
        targetDirs = [
            './**',// all files and sub folders.
            '!.*/**',// skip all .xxx folders.
            '!config/development/**',
            '!config/production/**',
            '!gulpfile.js/**',
            '!node_modules/**',
            '!Tests/**',
            '!mkdocs.yml',
            '!package*.json',
            '!phpunit.xml',
        ];
    } else {
        targetDirs = [
            './**',// all files and sub folders.
            '!.backup/**',
            '!.git/**',
            '!.dist/**',
            '!config/development/**',
            '!config/production/**',
            '!node_modules/**',
            '!package-*.json',
        ];
    }
    let zipFileName;
    if (isProduction === true) {
        zipFileName = 'RdbCMSA v' + moduleVersion + '.zip';
    } else {
        zipFileName = 'RdbCMSA dev.zip';
    }

    return src(targetDirs, {base : ".", dot: true})
        .pipe(print())
        .pipe(zip(zipFileName))
        .pipe(dest('.dist/'));
}// packDist


/**
 * Get module version from Install.php and write it to package.json.
 * 
 * @param {type} cb
 * @returns {unresolved}
 */
function writePackageVersion(cb) {
    let installerPhpContent = fs.readFileSync('./Installer.php', 'utf-8');
    let regexPattern = /@version(\s?)(?<version>[\d\.]+)/miu;
    let matched = installerPhpContent.match(regexPattern);
    let moduleVersion = 'unknown';
    if (matched && matched.groups && matched.groups.version) {
        moduleVersion = matched.groups.version;
    }

    let packageJson = JSON.parse(fs.readFileSync('./package.json'));
    packageJson.version = moduleVersion;

    fs.writeFileSync('./package.json', JSON.stringify(packageJson, null, 4));

    return cb();
}// writePackageVersion


exports.pack = series(
    writePackageVersion,
    packDist
);