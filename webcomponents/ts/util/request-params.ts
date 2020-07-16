/**
 * Request params class - reads the
 */
export default class RequestParams {

    /**
     * Get the request params.
     */
    static get(): any {

        var params = {};
        var parser = document.createElement('a');
        parser.href = window.location.href;
        var query = parser.search.substring(1);
        var vars = query.split('&');
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split('=');
            if (pair[1])
                params[pair[0]] = decodeURIComponent(pair[1].replace(/\+/g, '%20'));
        }
        return params;

    }


}
