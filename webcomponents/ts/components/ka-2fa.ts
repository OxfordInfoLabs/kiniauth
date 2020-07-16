import Validation from "../framework/validation";
import RequestParams from "../util/request-params";
import Api from "../framework/api";
import StandardForm from "./standard-form";

/**
 * Two factor authentication JS widget
 */
export default class Ka2fa extends StandardForm {

    /**
     * Construct new element
     */
    constructor() {
        super({
            "2fa": {
                "required": "A 2FA code is required"
            }
        });
    }


    public submitForm(fieldValues: any): Promise<any> {
        let api = new Api();
        return api.twoFactor(fieldValues["2fa"]);
    }



    public success(jsonResponse: any) {

        // Expect a boolean from this
        if (jsonResponse) {
            var signinSuccessURL = RequestParams.get().signinSuccessURL ? RequestParams.get().signinSuccessURL : "/";
            window.location.href = signinSuccessURL;
        } else {
            Validation.setFieldError(this, "2fa", true, "Invalid 2FA code supplied");
        }
    }


    public failure(jsonResponse: any) {
        Validation.setFieldError(this, "2fa", true, jsonResponse.message);
    }

}
