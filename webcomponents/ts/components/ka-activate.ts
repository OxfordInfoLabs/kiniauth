import Validation from "../framework/validation";
import Api from "../framework/api";
import RequestParams from "../util/request-params";
import StandardForm from "./standard-form";

/**
 * Account activation component.
 */
export default class KaActivate extends StandardForm {

    // Store success url
    private successUrl;

    constructor() {
        super({
            "code": {
                "required": "An activation code is required"
            }
        });

        // Resolve the success url, prioritising a passed request param, falling back to a data attribute or /
        this.successUrl = RequestParams.get().successUrl ? RequestParams.get().successUrl : (
            this.getAttribute("data-success-url") ? this.getAttribute("data-success-url") : "/");


    }

    // Submit the form
    public submitForm(fieldValues): Promise<any> {
        let api = new Api();
        return api.activateAccount(fieldValues.code);
    }

    // Success behaviour
    public success(jsonResponse) {
        window.location.href = this.successUrl;
    }

    // Failure behaviour
    public failure(jsonResponse) {

        let message = jsonResponse.validationErrors ? jsonResponse.validationErrors.activationCode.errorMessage : jsonResponse.message;
        Validation.setFieldError(this, "code", true, message);

    }



}
