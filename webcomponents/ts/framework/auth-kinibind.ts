import RequestParams from "../util/request-params";
import Session from "./session";
import Kinibind from "kinibind/ts/kinibind";

/**
 * Kinibind base class
 */
export default class AuthKinibind extends Kinibind {


    /**
     * Construct with an optional array of bound params.
     *
     * @param element
     * @param params
     */
    constructor(element, model = {}) {

        // Add core params
        let coreParams = {
            session: {},
            request: RequestParams.get(),
            state: {},
            now: new Date()
        };

        // Merge together and bind this element
        model = {...model, ...coreParams};

        // Initialise the combined model
        super(element, model);


        // Pump in the session once loaded provided we are not inheriting
        if (!model["_kinibindInherit"])
            Session.getSessionData().then((session => {
                this.model.session = session;
            }));

    }


}
