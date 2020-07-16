import StandardForm from "./standard-form";
import RequestParams from "../util/request-params";
import Api from "../framework/api";
import Configuration from "../configuration";


/**
 * Register component
 */
export default class KaInvitation extends StandardForm {


    // Invalid URL if invalid invitation code passed.
    private invalidUrl;

    // Success URL if successful activation occurs
    private successUrl;


    /**
     * Construct new element
     */
    constructor() {

        super({

            "name": {
                required: "The account name is required"
            },
            "password": {
                password: "Passwords must be at least 8 characters with one number, one uppercase and one lowercase letter",
                required: "A password is required"
            },
            "accept": {
                required: "you must accept the terms and conditions"
            }
        });


        // Resolve the success url, prioritising a passed request param, falling back to a data attribute or /
        this.successUrl =
            this.getAttribute("data-success-url") ? this.getAttribute("data-success-url") : "/";

        // Resolve the success url, prioritising a passed request param, falling back to a data attribute or /
        this.invalidUrl =
            this.getAttribute("data-invalid-url") ? this.getAttribute("data-invalid-url") : "/404";


        let api = new Api();
        api.getInvitationDetails(RequestParams.get()["code"]).then((response) => {
            if (response.ok) {

                response.json().then(jsonValue => {

                    // Show the new user form if we need to
                    if (jsonValue.newUser) {

                        Configuration.elementVisibilityFunction(this.querySelector("[data-new-user]"), true);

                        let email = this.querySelector("[data-email]");
                        if (email) email.innerHTML = jsonValue.emailAddress;

                        let accountName = this.querySelector("[data-account-name]");
                        if (accountName) accountName.innerHTML = jsonValue.accountName;

                    } else {
                        api.acceptInvitation(RequestParams.get()["code"], null, null).then(() => {
                            if (response.ok) {
                                window.location.href = this.successUrl;
                            } else {
                                alert("Something went wrong, please contact support");
                            }
                        });
                    }

                });

            } else {
                window.location.href = this.invalidUrl;
            }
        });


    }


    submitForm(fieldValues): Promise<any> {
        let api = new Api();
        return api.acceptInvitation(RequestParams.get()["code"], fieldValues.name, fieldValues.password);
    }

    failure(jsonResponse) {

    }

    success(jsonResponse) {
        window.location.href = this.successUrl;
    }


}
