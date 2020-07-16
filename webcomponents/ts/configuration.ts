/**
 * Static configuration class
 */
export default class Configuration {

    // Visibility toggle function
    private static _elementVisibilityFunction = function (element: HTMLElement, visible: boolean) {
        element.style.display = visible ? "default" : "none";
    };

    private static _endpoint: string;
    private static _recaptchaKey: string;
    private static _componentPrefix: string = "ka";


    static get elementVisibilityFunction(): (element: HTMLElement, visible: boolean) => void {
        return this._elementVisibilityFunction;
    }

    static set elementVisibilityFunction(value: (element: HTMLElement, visible: boolean) => void) {
        this._elementVisibilityFunction = value;
    }


    static get endpoint(): string {
        return this._endpoint;
    }

    static set endpoint(value: string) {
        this._endpoint = value;
    }


    static get recaptchaKey(): string {
        return this._recaptchaKey;
    }

    static set recaptchaKey(value: string) {
        this._recaptchaKey = value;
    }


    static get componentPrefix(): string {
        return this._componentPrefix;
    }

    static set componentPrefix(value: string) {
        this._componentPrefix = value;
    }
}
