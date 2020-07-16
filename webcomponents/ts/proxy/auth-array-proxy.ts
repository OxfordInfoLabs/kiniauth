import ArrayProxy from "kinibind/ts/proxy/array-proxy";
import FilteredResults from "kinibind/ts/proxy/filtered-results";

export default class AuthArrayProxy extends ArrayProxy {



    

    /**
     * Implement required method
     *
     * @param filters
     * @param sortOrders
     * @param offset
     * @param limit
     */
    protected filterResults(filters, sortOrders, offset, limit): Promise<FilteredResults> {
        return undefined;
    }


}
