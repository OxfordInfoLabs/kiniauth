import RequestParams from "../util/request-params";
import Api from "../framework/api";
import KaRecaptcha from "./ka-recaptcha";
import Configuration from "../configuration";
import ElementSpinner from "../util/element-spinner";
import AuthKinibind from "../framework/auth-kinibind";
import KaFileUpload from "./ka-file-upload";
import Validation from "../framework/validation";
// @ts-ignore
import * as dayjs from "dayjs";
// @ts-ignore
import * as customParseFormat from 'dayjs/plugin/customParseFormat';


/**
 * Register component
 */
export default class KaDynamicForm extends HTMLElement {

    // Kinibind view
    protected view;

    private errorsGenerated = false;


    // Construct a dynamic form dynamically
    constructor(extraData = {}, extraMethods = {}) {

        super();

        // Extend dayjs with custom parse format
        dayjs.extend(customParseFormat);

        this.init(extraData, extraMethods);

    }

    // Initialise the dynamic form
    private init(extraData = {}, extraMethods = {}) {

        let store = this.getAttribute("data-store");

        // If a new form, clear storage and reload without new form
        if (RequestParams.get().newForm) {
            sessionStorage.removeItem(store);
            window.location.replace(window.location.href.replace(/[\?&]newForm=1/, ''));
            return;
        }


        let rawData = sessionStorage.getItem(store);
        let data = rawData ? JSON.parse(rawData) : {};


        let defaultDataString = this.getAttribute("data-default-data");
        if (defaultDataString) {
            let defaultData: any = JSON.parse(defaultDataString.trim());
            data = {...defaultData, ...data};
        }

        let model = {
            data: data,
            errors: {},
            serverErrors: null,
            dateTime: new Date().toDateString() + " " + new Date().toLocaleTimeString(),
            date: new Date().toDateString(),
            valid: false,
            submitted: false
        };

        model = {...extraData, ...model};

        let methods = {
            setDataItem: (key, value) => {
                this.setDataItem(key, value);
            },
            toggleMulti: (key, setValue) => {

                let values = [];

                let data = this.view.model.data;

                if (data[key] instanceof Array) {
                    values = data[key];
                }
                let existingValue = values.indexOf(setValue);
                if (existingValue >= 0) {
                    values.splice(existingValue, 1);
                } else {
                    values.push(setValue);
                }

                this.setDataItem(key, values);

            }
        };

        model = {...model, ...extraMethods, ...methods};


        // Create the view
        this.view = new AuthKinibind(this,
            model
        );


        this.addEventListener("submit", (event) => {
            event.preventDefault();

            // Validate the form
            let valid = this.validate(true);

            // If valid process submission.
            if (valid) {
                this.processSubmission();
            }

        })

        // Add change and click listeners to keep valid in check.
        this.addEventListener("input", () => {
            this.validate(false);
        })

        this.addEventListener("click", () => {
            this.validate(false);
        })

        // Do an initial validation to generate the valid flag
        this.validate(false);

    }


    // Process submission
    protected processSubmission() {

        let captcha = <KaRecaptcha>this.querySelector(Configuration.componentPrefix + "-recaptcha");
        let captchaResponse = null;
        if (captcha) {
            captchaResponse = captcha.getResponse();
        }

        // Spin the button
        this.setButtonSpinStatus(true);
        this.view.model.serverErrors = null;

        let fileUploaders = this.querySelectorAll(Configuration.componentPrefix + "-file-upload");
        if (fileUploaders.length > 0) {

            let fileUploaderArray = [];
            fileUploaders.forEach((item) => {
                fileUploaderArray.push(item);
            })

            this.processFileUploaders(fileUploaderArray, captchaResponse).then(() => {
                this.doSubmit(captchaResponse);
            }).catch((e) => {
                this.view.model.serverErrors = {
                    "general": {
                        "error": {
                            "errorMessage": e.message
                        }
                    }
                };
                this.setButtonSpinStatus(false);
            })

        } else {
            this.doSubmit(captchaResponse);
        }

    }

    // Process file uploaders sequentiallhy
    private processFileUploaders(fileUploaders, captchaResponse): Promise<any> {

        return new Promise<any>((success, failure) => {
            if (fileUploaders.length > 0) {
                let fileUploader = fileUploaders.shift();
                (<KaFileUpload>fileUploader).upload(captchaResponse).then(() => {
                    this.processFileUploaders(fileUploaders, captchaResponse).then(() => {
                        success();
                    });
                }).catch(message => {
                    failure(message);
                });
            } else {
                success();
            }

        });


    }

    // Actually do the submot
    private doSubmit(captchaResponse: string) {


        let data = this.view.model.data;

        let submitUrl = this.getAttribute("data-submit-url");


        if (submitUrl) {
            let api = new Api();
            api.callAPI(submitUrl, data, "POST", captchaResponse).then((response) => {

                response.json().then((data) => {

                    // If OK response, continue
                    if (response.ok) {
                        this.processSuccess(data);
                    } else {

                        let serverErrors = {};

                        if (data.validationErrors) {
                            serverErrors = data.validationErrors;
                        } else {
                            serverErrors = {
                                "general": {
                                    "error": {
                                        "errorMessage": data.message
                                    }
                                }
                            };
                        }

                        this.view.model.serverErrors = serverErrors;

                        this.setButtonSpinStatus(false);

                    }


                });


            });
        } else {

            // Store the data in session storage
            let store = this.getAttribute("data-store");
            sessionStorage.setItem(store, JSON.stringify(data));

            // Process success
            this.processSuccess();

        }
    }

// Continue to next page
    private processSuccess(identifier = null) {

        let successUrl = this.getAttribute("data-success-url");

        if (successUrl) {
            window.location.href = successUrl + (identifier ? "?identifier=" + identifier : "");
        } else {

            this.setButtonSpinStatus(false);

            let captcha = <KaRecaptcha>this.querySelector(Configuration.componentPrefix + "-recaptcha");
            if (captcha) {
                captcha.reset();
            }

            this.view.model.submitted = true;

            let successModel = this.getAttribute("data-success-model");
            if (successModel) {
                this.view.model[successModel] = identifier;
            }

        }
    }

    // Validate the form
    protected validate(generateErrors) {

        let valid = true;
        let errors = {};

        if (!this.view) {
            return false;
        }

        let data = this.view.model.data;

        this.querySelectorAll("[data-validation]").forEach((item) => {
            let field = item.getAttribute("data-field");
            let validations = item.getAttribute("data-validation").split(",");

            let dataValue = data[field];

            // Now validate the value
            validations.forEach((validation) => {

                let fieldValid = true;

                switch (validation.trim()) {
                    case "required":
                        fieldValid = fieldValid && (dataValue instanceof Array ? dataValue.length > 0 : dataValue);
                        break;
                    case "email":
                        fieldValid = fieldValid && (!dataValue || dataValue.match(Validation.emailRegexp));
                        break;
                    case "date":
                        fieldValid = fieldValid && (!dataValue || (dayjs(dataValue, item.getAttribute("date-format") ? item.getAttribute("date-format") : 'YYYY-MM-DD').isValid()));
                        break;
                    case "maxwords":
                        fieldValid = fieldValid && (!dataValue || dataValue.split(/\W/).length <= Number(item.getAttribute("data-max-words")));
                        break;
                }

                if (item.tagName.toLowerCase().includes("-file-upload")) {
                    fieldValid = fieldValid && (<KaFileUpload>item).validate(validation);
                }

                if (!fieldValid) errors[field] = validation;

                valid = valid && fieldValid;
            })

        });

        let captcha = <KaRecaptcha>this.querySelector(Configuration.componentPrefix + "-recaptcha");

        if (captcha && !captcha.getResponse()) {
            errors["recaptcha"] = "required";
            valid = false;
        }


        let modelErrors = this.view.model.errors;

        if (generateErrors) {
            this.view.model.errors = errors;
            this.errorsGenerated = true;
        } else {
            this.view.model.errors = {};
        }

        this.view.model.valid = valid;

        return valid;
    }


    private setButtonSpinStatus(spinning) {
        // Set spinning status
        this.querySelectorAll("[type='submit']").forEach((element: any) => {

            if (spinning) {
                ElementSpinner.spinElement(element);
            } else {
                ElementSpinner.restoreElement(element);
            }

        });
    }


    /**
     * Set a data item
     *
     * @param key
     * @param value
     */
    protected setDataItem(key, value) {
        let data = this.view.model.data;
        data[key] = value;
    }


    /**
     * Get data item
     *
     * @param key
     */
    protected getDataItem(key) {
        let data = this.view.model.data;
        return data[key];
    }
}
