import Api from "./api";
import * as dayjs from "dayjs";
import RequestParams from "../util/request-params";

export default class Session {

    private static sessionData: any;

    /**
     * Get session data once
     */
    public static getSessionData(): Promise<any> {
        if (!this.sessionData) {
            let api = new Api();
            this.sessionData = new Promise<any>(done => {

                let sessionReload = RequestParams.get()["sessionReload"];

                let sessionData = sessionStorage.getItem("kaSession");
                let sessionExpiry = sessionStorage.getItem("kaSessionExpiry");
                let now = dayjs().format('YYYY-MM-DD HH:mm:ss');

                if (sessionReload || !sessionData || sessionExpiry < now) {
                    api.getSessionData().then(sessionData => {

                        // Store data
                        sessionStorage.setItem("kaSession", JSON.stringify(sessionData));

                        // Set expiry of session
                        let sessionExpiry = dayjs().add(20, 'minute').format('YYYY-MM-DD HH:mm:ss');
                        sessionStorage.setItem("kaSessionExpiry", sessionExpiry);

                        done(sessionData);
                    });
                } else {
                    done(JSON.parse(sessionData));
                }
            });
        }
        return this.sessionData;

    }


    /**
     * Clear session data
     */
    public static clearSessionData() {
        sessionStorage.removeItem("kaSession");
        sessionStorage.removeItem("kaSessionExpiry");
        this.sessionData = null;
    }

}
