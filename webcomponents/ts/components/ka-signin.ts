/**
 * Sign in form component for Kini Auth based systems
 */
import Validation from "../framework/validation";
import RequestParams from "../util/request-params";
import Api from "../framework/api";
import StandardForm from "./standard-form";
import Session from "../framework/session";


export default class KaSignin extends StandardForm {


    private twoFactorURL = this.getAttribute("data-two-factor-url");
    private closeSessionsURL = this.getAttribute("data-close-sessions-url");

    /**
     * Construct new element
     */
    constructor() {

        super({
            "email": {
                required: "The email address is required"
            },
            "password": {
                required: "A password is required"
            }
        }, false);

        if (this.recaptcha) {
            Session.getSessionData().then(session => {
                if (session.delayedCaptchas && session.delayedCaptchas["guest/auth/login"]) {
                    this.recaptcha.render();
                }
            });
        }

    }


    public submitForm(fieldValues: any): Promise<any> {
        let api = new Api();
        return api.login(fieldValues.email, fieldValues.password,
            this.recaptcha.isRendered() ? this.recaptcha.getResponse() : null);
    }

    public success(jsonResponse: any) {

        var signinSuccessURL = RequestParams.get().signinSuccessURL ? RequestParams.get().signinSuccessURL :
            (this.getAttribute("data-success-url") ? this.getAttribute("data-success-url") : "/");

        if (jsonResponse === 'REQUIRES_2FA') {
            if (this.twoFactorURL) {
                window.location.href = this.twoFactorURL + '?signinSuccessURL=' + signinSuccessURL;
            } else {
                alert("No 2FA URL has been configured for two factor logins");
            }
        } else if (jsonResponse == 'ACTIVE_SESSION') {
            if (this.closeSessionsURL) {
                window.location.href = this.closeSessionsURL + '?twoFactorURL=' + this.twoFactorURL + '&signinSuccessURL=' + signinSuccessURL;
            } else {
                alert("No close sessions URL has been configured");
            }
        } else {
            window.location.href = signinSuccessURL;
        }


    }

    public failure(jsonResponse: any) {
        Validation.setFieldError(this, "password", true, jsonResponse.message);
    }


}
