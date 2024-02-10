/**
 * RundizBones CMS admin JS Utilities.
 */


class RdsUtils {


    /**
     * Convert the input URL to URL safe string with lower case.
     * 
     * @param {string} url The input URL.
     * @returns {String} Return converted URL safe string.
     */
    static convertUrlSafeString(url) {
        let urlArray = url.split('/');
        urlArray.forEach(function(item, index) {
            let safeSegment = RdbaCommon.removeUnsafeUrlCharacters(_.trim(item));
            safeSegment = safeSegment.toLowerCase();
            item = safeSegment;
            urlArray[index] = item;
        });

        return urlArray.join('/');
    }// convertUrlSafeString


    /**
     * Make delay before call the function.
     * 
     * Usage: `RdsUtils.delay(function() {alert('hello');}, 1000);`.
     * 
     * @link https://stackoverflow.com/a/1909508/128761 Original source code.
     * @param {function} fn
     * @param {int} ms
     * @returns {function}
     */
    static delay(fn, ms) {
        let timer = 0
        return function (...args) {
            clearTimeout(timer)
            timer = setTimeout(fn.bind(this, ...args), ms || 0)
        }
    }// delay


}// RdsUtils