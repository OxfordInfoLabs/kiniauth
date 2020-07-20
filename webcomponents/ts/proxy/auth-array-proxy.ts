import ArrayProxy from "kinibind/ts/proxy/array-proxy";
import FilteredResults from "kinibind/ts/proxy/filtered-results";
import FilterQuery from "kinibind/ts/proxy/filter-query";
import Api from "../framework/api";
import * as dayjs from "dayjs";


export default class AuthArrayProxy extends ArrayProxy {

    private _sourceUrl;

    // Running queries
    private static lastQueryStarts = {};

    constructor(sourceUrl: string) {
        super();
        this._sourceUrl = sourceUrl;
    }


    /**
     * Implement required method
     *
     * @param filters
     * @param sortOrders
     * @param offset
     * @param limit
     */
    public filterResults(filters: FilterQuery): Promise<FilteredResults> {


        let passedFilters = {...filters};


        let strippedFilters = {};
        if (passedFilters.filters) {
            Object.keys(passedFilters.filters).forEach(key => {
                if (key !== "__rv") {
                    let filterValue = passedFilters.filters[key];
                    if ((typeof filterValue != "string") && filterValue && filterValue.type) {
                        strippedFilters[key] = filterValue.value ? filterValue.value : "";
                    } else {
                        strippedFilters[key] = filterValue;
                    }
                }
            });
            passedFilters.filters = strippedFilters;
        }

        if (passedFilters.sortOrders) {
            let strippedOrders = [];
            passedFilters.sortOrders.forEach(order => {
                strippedOrders.push(order.member + " " + order.direction)
            });
            passedFilters.sortOrders = strippedOrders;
        }

        return new Promise<FilteredResults>(done => {
            let api = new Api();

            let myStartTime = dayjs().valueOf();
            AuthArrayProxy.lastQueryStarts[filters.hash] = myStartTime;

            api.callAPI(this._sourceUrl, passedFilters, "POST").then((response => {

                if (AuthArrayProxy.lastQueryStarts[filters.hash] <= myStartTime) {

                    if (response.ok) {
                        response.json().then(result => {
                            if (result instanceof Array) {
                                done(new FilteredResults(result, null));
                            } else if (result && result.results) {
                                done(new FilteredResults(result.results, result.totalCount));
                            }
                        });

                    } else {
                        done(new FilteredResults([], 0));
                    }
                }
            }));
        });
    }


}
