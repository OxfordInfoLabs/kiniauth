/**
 * Sign in form component for Kini Auth based systems
 */

import Api from "../framework/api";

export default class KaSignout extends HTMLElement {

    private redirectUrl = this.getAttribute("data-redirect-url");

    /**
     * Constructor for signout
     */
    constructor() {
        super();

        // Add the click event
        this.addEventListener("click", () => {
            let api = new Api();
            api.logout().then(() => {

                // Redirect to the passed url or to / as fallback.
                window.location.href = this.redirectUrl ? this.redirectUrl : "/";

            });

        })
    }


}
