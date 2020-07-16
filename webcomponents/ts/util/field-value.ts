/**
 * Static field values class
 */
export default class FieldValue {

    /**
     * Get the normalised field value for an element.
     *
     * @param element
     */
    public static get(element: HTMLElement) {

        if (element instanceof HTMLInputElement) {

            if (element.type == "checkbox") {
                return element.checked;
            } else if (element.type == "radio") {
                let elementName = element.name;
                let match = document.querySelector("input[name='" + elementName + "']:checked");
                if (match) {
                    return match.getAttribute("data-value");
                } else {
                    return null;
                }
            } else {
                return element.value;
            }

        } else if (element instanceof HTMLSelectElement) {
            return element.value;
        }

    }

}
