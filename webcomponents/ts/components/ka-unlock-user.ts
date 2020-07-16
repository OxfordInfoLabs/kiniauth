/**
 * Unlock user component
 */

import Api from "../framework/api";
import RequestParams from "../util/request-params";
import Configuration from "../configuration";

export default class KaUnlockUser extends HTMLElement {

    /**
     * Constructor for unlock user
     */
    constructor() {
        super();

        let api = new Api();
        api.unlockUser(RequestParams.get()["unlockCode"]).then((response) => {

            if (response.ok) {
                Configuration.elementVisibilityFunction(this.querySelector("[data-unlocked]"), true);
            } else {
                // Redirect to the passed url or to / as fallback.
                window.location.href = this.getAttribute("data-invalid-url") ? this.getAttribute("data-invalid-url") : "/404";
            }
        }).catch(() => {

            // Redirect to the passed url or to / as fallback.
            window.location.href = this.getAttribute("data-invalid-url") ? this.getAttribute("data-invalid-url") : "/404";

        });


    }
}
