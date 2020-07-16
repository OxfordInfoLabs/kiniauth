/**
 * Validation utility classes.
 */
import Configuration from "../configuration";
import KaRecaptcha from "../components/ka-recaptcha";
import FieldValue from "../util/field-value";

export default class Validation {

    static emailRegexp = /(?:[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/;

    /**
     * Reset multiple fields for validation within the containing element by identifiers using the convention pattern
     *
     * @param containingElement
     * @param identifiers
     */
    static resetFields(containingElement: Element, identifiers: string[]) {

        identifiers.forEach((item) => {
            this.setFieldError(containingElement, item, false, "");
        });


    }


    /**
     * Validate required fields
     *
     * @param containingElement
     * @param identifiers
     */
    static validateRequiredFields(containingElement: Element, identifiers: any) {

        let valid: boolean = true;

        Object.keys(identifiers).forEach((item) => {


            var element: Element = containingElement.querySelector("[data-" + this.camelToHyphen(item) + "-field]");
            if (element instanceof HTMLInputElement) {

                let elementValid: boolean;

                if (element.type == "checkbox") {
                    elementValid = element.checked;
                } else {
                    elementValid = (element.value != null && element.value.length > 0);
                }

                if (!elementValid) {
                    this.setFieldError(containingElement, item, true, identifiers[item]);
                }
                valid = valid && elementValid;
            }
        });

        return valid;

    }

    /**
     * Validate email fields
     *
     * @param param
     * @param identifiers
     */
    static validateEmailFields(containingElement: Element, identifiers: any) {
        let valid: boolean = true;

        Object.keys(identifiers).forEach((item) => {
            var element: Element = containingElement.querySelector("[data-" + this.camelToHyphen(item) + "-field]");

            if (element instanceof HTMLInputElement) {

                let value = element.value;

                let elementValid = value.match(this.emailRegexp) ? true : false;

                if (!elementValid) {
                    this.setFieldError(containingElement, item, true, identifiers[item]);
                }

                valid = valid && elementValid;


            }
        });

        return valid;
    }


    /**
     * Validate password fields
     *
     * @param containingElement
     * @param identifiers
     */
    static validatePasswordFields(containingElement: Element, identifiers: any) {
        let valid: boolean = true;

        Object.keys(identifiers).forEach((item) => {
            var element: Element = containingElement.querySelector("[data-" + this.camelToHyphen(item) + "-field]");

            if (element instanceof HTMLInputElement) {

                let value = element.value;

                let elementValid = value.match(/[A-Z]/) && value.match(/[a-z]/) &&
                    value.match(/[0-9]/) && value.length >= 8;

                if (!elementValid) {
                    this.setFieldError(containingElement, item, true, identifiers[item]);
                }

                valid = valid && elementValid;

            }

        });

        return valid;
    }


    /**
     * Validate username fields
     *
     * @param containingElement
     * @param identifiers
     */
    static validateUsernameFields(containingElement: Element, identifiers: any) {
        let valid: boolean = true;

        Object.keys(identifiers).forEach((item) => {
            var element: Element = containingElement.querySelector("[data-" + this.camelToHyphen(item) + "-field]");

            if (element instanceof HTMLInputElement) {

                let value = element.value;

                let elementValid = value.match(/^[a-zA-Z0-9-_]+$/) !== null;

                if (!elementValid) {
                    this.setFieldError(containingElement, item, true, identifiers[item]);
                }

                valid = valid && elementValid;

            }

        });

        return valid;
    }


    /**
     * Validate a field equalling another field
     *
     * @param containingElement
     * @param field
     * @param otherField
     * @param message
     */
    static validateEqualsFields(containingElement: Element, field: string, otherField: string, message: string) {


        let fieldElement: HTMLElement = containingElement.querySelector("[data-" + this.camelToHyphen(field) + "-field]");
        let otherFieldElement: HTMLElement = containingElement.querySelector("[data-" + this.camelToHyphen(otherField) + "-field]");

        let fieldValue = FieldValue.get(fieldElement);
        let valid: boolean = fieldValue == FieldValue.get(otherFieldElement);

        if (!valid)
            this.setFieldError(containingElement, field, true, message);

        return valid;
    }


    /**
     * Validate recaptcha field
     *
     * @param containingElement
     * @param message
     */
    static validateRecaptcha(containingElement: Element, message: string) {

        let recaptchas = containingElement.getElementsByTagName(Configuration.componentPrefix + "-recaptcha");

        if (recaptchas.length > 0) {

            let recaptcha = <KaRecaptcha>recaptchas.item(0);

            let valid = recaptcha.getResponse() ? true : false;

            this.setFieldError(containingElement, "recaptcha", !valid, message);

            return valid;
        } else {
            return true;
        }


    }


    // Set a field error (visibility and message).
    static setFieldError(containingElement: Element, identifier: string, visible: boolean, message: string) {
        let element: HTMLElement = containingElement.querySelector("[data-" + this.camelToHyphen(identifier) + "-error]");
        if (element) {
            element.innerHTML = message;
            Configuration.elementVisibilityFunction(element, visible);
        }
    }


    // Convert camel case to hypen.
    static camelToHyphen(string) {
        return string.replace(/[A-Z]/g, function (match) {
            return "-" + match.toLowerCase();
        });
    }


}
