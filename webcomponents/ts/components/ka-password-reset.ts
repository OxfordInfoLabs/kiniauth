import Api from "../framework/api";
import Configuration from "../configuration";
import StandardForm from "./standard-form";

/**
 * Account activation component.
 */
export default class KaPasswordReset extends StandardForm {

    constructor() {
        super({
            "email": {
                "required": "You must supply an email address"
            }
        });
    }


    public submitForm(fieldValues: any): Promise<any> {

        let api = new Api();
        return api.requestPasswordReset(fieldValues.email, this.recaptcha.getResponse());
    }

    public success(jsonResponse: any) {
        this.complete();
    }

    public failure(jsonResponse: any) {
        this.complete();
    }


    private complete() {
        // Regardless of the response, show alternative div
        Configuration.elementVisibilityFunction(this.querySelector("form"), false);
        Configuration.elementVisibilityFunction(this.querySelector("[data-password-reset-sent]"), true);
    }


}
