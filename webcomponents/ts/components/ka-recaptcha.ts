/**
 * Recaptcha component if being used.
 */
import Configuration from "../configuration";

export default class KaRecaptcha extends HTMLElement {

    // Static instance index
    private static instanceIndex = 0;

    // Component id
    private componentId;

    // Returned instance id for Captcha
    private instanceId;

    // Rendered flag
    private rendered = false;

    /**
     * Constructor
     */
    constructor() {

        super();

        if (Configuration.recaptchaKey) {

            KaRecaptcha.instanceIndex++;
            this.componentId = "recaptcha_" + KaRecaptcha.instanceIndex;

            if (KaRecaptcha.instanceIndex == 1)
                this.loadScript();


            // Render immediately if autoshow
            if (this.hasAttribute("data-autoshow")) {
                this.render();
            }

        } else {
            alert("You need to configure a recaptcha key to use the ka-recaptcha component");
        }
    }


    /**
     * Render this recaptcha instance.
     */
    public render() {

        // Ensure this element is visible
        Configuration.elementVisibilityFunction(this, true);

        if (!this.rendered) {
            let instance = document.createElement("div");
            instance.id = this.componentId;
            this.appendChild(instance);

            if (!window["grecaptcha"] || !window["grecaptcha"].render) {
                setTimeout(() => {
                    this.render()
                }, 500);
                return;
            } else {

                this.instanceId = window["grecaptcha"].render(this.componentId, {
                    'sitekey': Configuration.recaptchaKey
                });

            }


            this.rendered = true;
        } else {
            this.reset();
        }
    }


    /**
     * Reset the captcha
     */
    public reset() {
        window["grecaptcha"].reset(this.instanceId);
    }


    /**
     * Return a boolean indicating whether or not this captcha has been rendered
     */
    public isRendered() {
        return this.rendered;
    }

    /**
     * Check whether or not this recaptcha is completed.
     */
    public getResponse(): string {
        if (window['grecaptcha'])
            return window["grecaptcha"].getResponse(this.instanceId);
        else
            return null;
    }


    // Load the script
    private loadScript() {

        let script: HTMLScriptElement = document.createElement("script");
        script.type = "text/javascript";
        script.src = "https://www.google.com/recaptcha/api.js";

        document.getElementsByTagName("body")[0].appendChild(script);

    }


}
