import Validation from "../framework/validation";
import Api from "../framework/api";
import Configuration from "../configuration";
import StandardForm from "./standard-form";
import RequestParams from "../util/request-params";

/**
 * Account activation component.
 */
export default class KaNewPassword extends StandardForm {

    constructor() {
        super({
            "password": {
                "password": "Passwords must be at least 8 characters with one number, one uppercase and one lowercase letter",
                "required": "You must enter a password",
            },
            "confirm": {
                "equals": {
                    "message": "The confirm password must match the new password",
                    "otherField": "password"
                },
                "required": "You must enter a confirm password"
            }
        });



    }


    public submitForm(fieldValues: any): Promise<any> {
        let api = new Api();
        return api.resetPassword(fieldValues.password, RequestParams.get()["code"], this.recaptcha.getResponse());
    }

    public success(jsonResponse: any) {
        Configuration.elementVisibilityFunction(this.querySelector("form"), false);
        Configuration.elementVisibilityFunction(this.querySelector("[data-password-reset-complete]"), true);
    }

    public failure(jsonResponse: any) {
        let message = jsonResponse.validationErrors ? jsonResponse.validationErrors.resetCode.errorMessage : jsonResponse.message;
        Validation.setFieldError(this, "confirm", true, message);
    }

}
