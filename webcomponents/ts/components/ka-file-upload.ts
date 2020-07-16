/**
 * Generic file upload class - wraps a native file upload input to provide javascript
 * based uploads.
 */
import Api from "../framework/api";

export default class KaFileUpload extends HTMLElement {

    // Reference to contained file field
    private fileField: HTMLInputElement;

    // Values array
    private values = [];

    // Uploaded boolean
    private uploaded = false;


    // Constructor
    constructor() {
        super();
        this.init();
    }


    // Initialisation for this file upload
    private init() {

        // Track the file field
        this.fileField = this.querySelector("input");

        // On change, update our values
        this.fileField.addEventListener("change", () => {

            this.values = [];

            let files = this.fileField.files;
            if (files && files.length > 0) {
                for (let i = 0; i < files.length; i++) {
                    this.values.push({
                        "name": files[i].name,
                        "size": files[i].size,
                        "type": files[i].type
                    });
                }
            }

            this.uploaded = false;

            let event = document.createEvent("Event");
            event.initEvent("input", false, true);
            this.dispatchEvent(event);

        });


    }

    // Validate this file object
    public validate(validationType) {

        let valid = true;


        if (validationType == "filesize" && this.getAttribute("data-max-size")) {

            let maxSize = Number(this.getAttribute("data-max-size"));


            let totalLength = 0;
            for (let i = 0; i < this.values.length; i++) {
                totalLength += this.values[i].size;
            }
            let totalLengthMB = totalLength / (1024 * 1024);

            if (totalLengthMB > maxSize) {
                valid = false;
            }

        }

        if (validationType == "filetype" && this.getAttribute("data-file-types")) {
            let fileTypes: string[] = this.getAttribute("data-file-types").split(",");
            for (let i = 0; i < this.values.length; i++) {
                let extension = "." + this.values[i].name.split(".").pop();
                if (fileTypes.indexOf(extension) < 0) {
                    valid = false;
                }
            }
        }


        return valid;
    }


    /**
     * Upload the contained file
     */
    public upload(captcha: string = ""): Promise<any> {

        return new Promise<any>((done, reject) => {

            // Shortcut if upload has already been done.
            if (this.uploaded || this.values.length == 0) {
                done();
                return;
            }

            let uploadUrlProvider = this.getAttribute("data-upload-url-provider");
            if (uploadUrlProvider) {

                let api = new Api();

                api.callAPI(uploadUrlProvider,
                    this.values
                    , "POST",captcha).then((response) => {

                    if (response.ok) {

                        response.json().then(result => {

                            result.forEach((item, index) => {
                                this.values[index].storedName = item.filename;
                            });

                            this.uploadAllFiles(result, 0).then(() => {
                                let event = document.createEvent("Event");
                                event.initEvent("input", false, true);
                                this.dispatchEvent(event);
                                done();
                            }).catch(() => {
                                reject("File upload failed");
                            })


                        });


                    } else {
                        reject("File upload failed");
                    }

                });
            }

        });
    }


    // Upload all files
    private uploadAllFiles(remainingUploads: any[], currentIndex): Promise<any> {

        return new Promise<any>((done, reject) => {
            if (remainingUploads.length > 0) {

                let currentUpload = remainingUploads.shift();

                let xhr = new XMLHttpRequest();
                xhr.open("PUT", currentUpload.uploadUrl);

                let reader = new FileReader();
                reader.addEventListener("load", function (evt:any) {
                    xhr.send(evt.target.result);
                });

                xhr.addEventListener("load", () => {
                    this.uploadAllFiles(remainingUploads, currentIndex + 1).then(() => {
                        done();
                    });
                });

                reader.readAsBinaryString(this.fileField.files.item(currentIndex));

            } else {
                done();
            }
        });

    }

    // Return the bound value for this
    public get value() {
        return this.values;
    }


    // Set the bound value for this upload.
    // Implemented for compliance but not required
    public set value(value) {
    }


}

