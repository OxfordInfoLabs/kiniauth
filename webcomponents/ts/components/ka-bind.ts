/**
 * Generic binder which binds a model to an AJAX source.  A source and model must be supplied in the form
 * of data attributes (data-source and data-model) and optionally a defer-load attribute may be supplied to allow
 * for event driven loading and reloading later.
 *
 * A sub container with class vue-wrapper must be supplied.
 *
 */
import Api from "../framework/api";
import RequestParams from "../util/request-params";
import AuthKinibind from "../framework/auth-kinibind";
import AuthArrayProxy from "../proxy/auth-array-proxy";


export default class KaBind extends HTMLElement {

    // The Vue instance
    private view;


    constructor() {
        super();
        this.init();
    }


    /**
     * Get observed attributes
     */
    static get observedAttributes() {
        return ['data-source'];
    }


    /**
     * Changed callback
     *
     * @param name
     * @param oldValue
     * @param newValue
     */
    attributeChangedCallback(name, oldValue, newValue) {

        if (name == 'data-source' && newValue)
            this.load();
    }


    // Initialise
    private init() {


        let model = this.getAttribute("data-model");

        let source = this.getAttribute("data-source");
        let sourceKey = this.getAttribute("data-source-key");


        if (source && !sourceKey) {
            alert("You must supply a source key when supplying a source for a bind");
        }

        let data: any = {};


        // Handle both inline string and object models
        if (typeof model == "string") {
            try {
                let jsonModel = JSON.parse(model);
                data = jsonModel ? jsonModel : {};
            } catch (e) {
                data = {};
            }

        } else if (typeof model == "object")
            data = model ? model : {};


        this.view = new AuthKinibind(this, data);

        if (source && !this.getAttribute("defer-load")) {
            this.load();
        }

    }

    // Load the source
    private load() {

        let url = this.getAttribute("data-source");
        url = url.replace(/\{request\.([a-zA-Z]+?)\}/g,
            (match, identifier): string => {
                return RequestParams.get()[identifier];
            });


        let sourceKey = this.getAttribute("data-source-key");

        if (this.getAttribute("data-proxy")) {

            this.view.model[sourceKey] = AuthArrayProxy.create(url);

        } else {

            let api = new Api();

            api.callAPI(url).then((results) => {

                if (results.ok) {

                    if (this.getAttribute("data-raw-response")) {


                        results.text().then(model => {
                            this.view.model[sourceKey] = model;
                            let event = document.createEvent("Event");
                            event.initEvent("sourceLoaded", false, true);
                            this.dispatchEvent(event);
                        });


                    } else {

                        results.json().then(model => {
                            this.view.model[sourceKey] = model;
                            let event = document.createEvent("Event");
                            event.initEvent("sourceLoaded", false, true);
                            this.dispatchEvent(event);
                        });
                    }

                } else {

                    if (this.getAttribute("data-raw-response")) {
                        results.text().then(model => {
                            this.view.model[sourceKey + "Error"] = model;
                        });
                    } else {
                        results.json().then(model => {
                            this.view.model[sourceKey + "Error"] = model;
                        });
                    }

                }

            });
        }


    }

}
