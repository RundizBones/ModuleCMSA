/**
 * Countries view JS for its controller.
 */


// namespace
const RdbCMSA = {
    Tools: {
        Countries: {
        }
    }
};


RdbCMSA.Tools.Countries.IndexController = class extends RdbaDatatables {


    /**
     * Class constructor.
     * 
     * @param {object} options
     * @returns {RdbaRolesController}
     */
    constructor(options) {
        super(options);

        this.formIDSelector = '#rdbcmsaToolsCountries-list-form';
        this.datatableIDSelector = '#rdbcmsaToolsCountriesTable';
        this.defaultSortOrder = [];
    }// constructor


    /**
     * Activate data table.
     * 
     * @returns {undefined}
     */
    activateDataTable() {
        let $ = jQuery.noConflict();
        let thisClass = this;
        let addedCustomResultControls = false;

        $.when(uiXhrCommonData)// uiXhrCommonData is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
        .done(function() {
            let dataTable = $(thisClass.datatableIDSelector).DataTable({
                'ajax': {
                    'url': RdbCMSAToolsCountriesIndexObject.getCountriesRESTUrl,
                    'method': RdbCMSAToolsCountriesIndexObject.getCountriesRESTMethod,
                    'dataSrc': 'listItems'// change array key of data source. see https://datatables.net/examples/ajax/custom_data_property.html
                },
                'autoWidth': false,// don't set style="width: xxx;" in the table cell.
                'columnDefs': [
                    {
                        'orderable': false,// make checkbox column not sortable.
                        'searchable': false,// make checkbox column can't search.
                        'targets': [0, 1, 2, 3, 4]
                    },
                    {
                        'className': 'control',
                        'targets': 0,
                        'render': function () {
                            // make first column render nothing (for responsive expand/collapse button only).
                            // this is for working with responsive expand/collapse column and AJAX.
                            return '';
                        }
                    },
                    {
                        'data': 'name',
                        'targets': 1,
                        'render': function(data, type, row, meta) {
                            let source = document.getElementById('rdba-datatables-row-actions').innerHTML;
                            let template = Handlebars.compile(source);
                            Handlebars.registerHelper('replace', function (find, replace, options) {
                                let string = options.fn(this);
                                return string.replace(find, replace);
                            });
                            row.RdbCMSAToolsCountriesIndexObject = RdbCMSAToolsCountriesIndexObject;
                            let html = '';
                            if (data !== null) {
                                let viewStatesUrl = RdbCMSAToolsCountriesIndexObject.getStatesRESTUrl.replace('%aplha3code%', row.alpha3code);
                                html = '<a class="view-states-link" href="' + viewStatesUrl + '">' + data + '</a>';
                            }
                            html += template(row);
                            return html;
                        }
                    },
                    {
                        'data': 'alpha2code',
                        'targets': 2
                    },
                    {
                        'data': 'alpha3code',
                        'targets': 3
                    },
                    {
                        'data': 'numericcode',
                        'targets': 4
                    }
                ],
                'dom': thisClass.datatablesDOM,
                'fixedHeader': true,
                'language': datatablesTranslation,// datatablesTranslation is variable from /assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js file
                'order': thisClass.defaultSortOrder,
                'pageLength': parseInt(RdbaUIXhrCommonData.configDb.rdbadmin_AdminItemsPerPage),
                'paging': false,
                'pagingType': 'input',
                'processing': true,
                'responsive': {
                    'details': {
                        'type': 'column',
                        'target': 0
                    }
                },
                'searchDelay': 1300,
                'serverSide': true,
                // state save ( https://datatables.net/reference/option/stateSave ).
                // to use state save, any custom filter should use `stateLoadCallback` and set input value.
                // maybe use keepconditions ( https://github.com/jhyland87/DataTables-Keep-Conditions ).
                'stateSave': false
            });//.DataTable()

            // datatables events
            dataTable.on('xhr.dt', function(e, settings, json, xhr) {
                if (addedCustomResultControls === false) {
                    // if it was not added custom result controls yet.
                    // set additional data.
                    json.RdbCMSAToolsCountriesIndexObject = RdbCMSAToolsCountriesIndexObject;
                    // add search controls.
                    thisClass.addCustomResultControls(json);
                    // add bulk actions controls.
                    thisClass.addActionsControls(json);
                    addedCustomResultControls = true;
                }

                // add pagination.
                thisClass.addCustomResultControlsPagination(json);

                if (json && json.formResultMessage) {
                    RdbaCommon.displayAlertboxFixed(json.formResultMessage, json.formResultStatus);
                }

                if (json) {
                    if (typeof(json.csrfKeyPair) !== 'undefined') {
                        RdbCMSAToolsCountriesIndexObject.csrfKeyPair = json.csrfKeyPair;
                    }
                }
            })// datatables on xhr complete.
            .on('draw', function() {
                // add listening events.
                thisClass.addCustomResultControlsEvents(dataTable);
            })// datatables on draw complete.
            ;
        });// uiXhrCommonData.done()
    }// activateDataTable


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        // activate data table.
        this.activateDataTable();
        // listen click view states link then get and display in the table.
        this.listenClickViewStatesLink();
    }// init


    /**
     * Listen click view states link then get and display in the table.
     * 
     * @returns {undefined}
     */
    listenClickViewStatesLink() {
        let thisClass = this;

        document.addEventListener('click', function(event) {
            if (
                RdbaCommon.isset(() => event.currentTarget.activeElement.classList) &&
                event.currentTarget.activeElement.classList.contains('view-states-link')
            ) {
                event.preventDefault();
                let thisLink = event.currentTarget.activeElement;

                // get current states placeholder
                let statesPlaceholder = thisLink.closest('td').querySelector('.states-placeholder');

                if (statesPlaceholder.innerHTML.length > 0) {
                    // if the placeholder (states) of this link is currently displaying.
                    // remove it to make it toggle show/hide.
                    statesPlaceholder.innerHTML = '';
                } else {
                    // if the placeholder (states) of this link is NOT currently displaying.
                    // clear all states/regions.
                    document.querySelectorAll('.states-placeholder').forEach(function(item, index) {
                        item.innerHTML = '';
                    });

                    RdbaCommon.XHR({
                        'url': thisLink.href,
                        'method': RdbCMSAToolsCountriesIndexObject.getStatesRESTMethod,
                        'dataType': 'json'
                    })
                    .catch(function(responseObject) {
                        // XHR failed.
                        let response = responseObject.response;

                        return Promise.reject(responseObject);
                    })
                    .then(function(responseObject) {
                        // XHR success.
                        let response = responseObject.response;

                        if (RdbaCommon.isset(() => response.listItems) && response.listItems.length > 0) {
                            // if the selected country contain states/regions.
                            let renderHtml = '<ul>';
                            for (let i = 0, max = response.listItems.length; i < max; i++) {
                                let item = response.listItems[i];
                                renderHtml += '<li>' + item['name'] + '</li>';
                            }
                            renderHtml += '</ul>';
                            statesPlaceholder.innerHTML = renderHtml;
                        } else {
                            // if the selected country does not contain states/regions.
                            let renderHtml = '<p class="text-color-warning"><em>' + RdbCMSAToolsCountriesIndexObject.txtCountryNoStateData + '</em></p>';
                            statesPlaceholder.innerHTML = renderHtml;
                        }

                        return Promise.resolve(responseObject);
                    });
                }
            }// endif; event element contains class.
        });// end add event listener.
    }// listenClickViewStatesLink


}// ToolsCountriesIndexController


document.addEventListener('DOMContentLoaded', function() {
    let rdbcmsaToolsCountriesIndexControllerClass = new RdbCMSA.Tools.Countries.IndexController();

    // initialize datatables.
    rdbcmsaToolsCountriesIndexControllerClass.init();
}, false);