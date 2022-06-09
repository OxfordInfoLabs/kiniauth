import {Injectable} from '@angular/core';
import {KiniAuthModuleConfig} from '../../ng-kiniauth.module';
import {KinibindRequestService} from 'ng-kinibind';
import {BehaviorSubject} from 'rxjs/internal/BehaviorSubject';
import * as _ from 'lodash';
import * as sha512 from 'js-sha512' ;
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {map} from 'rxjs/operators';

@Injectable({
    providedIn: 'root'
})
export class AuthenticationService {

    public authUser: BehaviorSubject<any> = new BehaviorSubject(null);
    public sessionData: BehaviorSubject<any> = new BehaviorSubject<any>(null);
    public loadingRequests: BehaviorSubject<boolean> = new BehaviorSubject<boolean>(false);

    constructor(private kbRequest: KinibindRequestService,
                private config: KiniAuthModuleConfig,
                private http: HttpClient) {

        const user = sessionStorage.getItem('loggedInUser');
        this.authUser.next(JSON.parse(user));

        const sessionData = sessionStorage.getItem('sessionData');
        if (sessionData && _.filter(JSON.parse(sessionData)).length) {
            this.sessionData.next(JSON.parse(sessionData));
        }
    }

    public getLoggedInUser(reloadSession?): any {
        let promise = Promise.resolve(true);
        if (reloadSession || !this.sessionData.getValue()) {
            promise = this.getSessionData();
        }
        return promise.then(() => {
            return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user').toPromise()
                .then(res => {
                    if (res) {
                        return this.setSessionUser(res).then(() => {
                            const sessionData = sessionStorage.getItem('sessionData');
                            if (sessionData && _.filter(JSON.parse(sessionData)).length) {
                                this.sessionData.next(JSON.parse(sessionData));
                                return res;
                            } else {
                                return this.getSessionData().then(() => {
                                    return res;
                                });
                            }
                        });
                    }
                    return null;
                });
        });
    }

    public login(username: string, password: string, recaptcha?) {
        const request = this.config.guestHttpURL + `/auth/login`;

        const headers = new HttpHeaders({'X-CAPTCHA-TOKEN': recaptcha || ''});
        const options: any = {headers};

        return this.kbRequest.makePostRequest(request, {
            emailAddress: username,
            password: this.getHashedPassword(password, username)
        }, options).toPromise().then((user: any) => {
            if (user === 'REQUIRES_2FA') {
                return user;
            } else {
                return this.getSessionData().then(() => {
                    return this.setSessionUser(user);
                });
            }
        });
    }

    public sendPasswordReset(emailAddress, recaptcha?) {
        const headers = new HttpHeaders({'X-CAPTCHA-TOKEN': recaptcha || ''});

        return this.http.get(this.config.guestHttpURL + '/auth/passwordReset', {
            params: {emailAddress},
            headers
        }).toPromise();
    }

    public getEmailForPasswordReset(resetCode) {
        return this.http.get(this.config.guestHttpURL + '/auth/passwordReset/' + resetCode)
            .toPromise();
    }

    public resetPassword(emailAddress, newPassword, resetCode, recaptcha) {
        const headers = new HttpHeaders({'X-CAPTCHA-TOKEN': recaptcha || ''});
        const options: any = {headers};

        return this.http.post(this.config.guestHttpURL + '/auth/passwordReset', {
            newPassword: this.getHashedPassword(newPassword, emailAddress, true),
            resetCode
        }, options).toPromise();
    }

    public changeUserPassword(newPassword, existingPassword, email) {
        return this.http.get(this.config.accessHttpURL + '/user/changeUserPassword', {
            params: {
                newPassword: this.getHashedPassword(newPassword, email, true),
                password: this.getHashedPassword(existingPassword, email)
            }
        }).toPromise();
    }

    public updateApplicationSettings(settings) {
        return this.http.put(this.config.accessHttpURL + '/user/applicationSettings', settings
        ).toPromise();
    }

    public isAdminNow() {
        const session = this.sessionData.getValue();
        if (session && session.privileges) {
            const accountId = session.account ? session.account.accountId : null;
            const privileges = session.privileges.ACCOUNT;

            if (privileges['*']) {
                return true;
            }

            return privileges[accountId] ? privileges[accountId].indexOf('*') > -1 : false;
        }
        return false;
    }

    public isAdmin() {
        return this.sessionData.pipe(map(session => {
            if (session && session.privileges) {
                const accountId = session.account ? session.account.accountId : null;
                const privileges = session.privileges.ACCOUNT;

                if (privileges['*']) {
                    return true;
                }

                return privileges[accountId] ? privileges[accountId].indexOf('*') > -1 : false;
            }
            return false;
        }));
    }

    public closeActiveSession() {
        return this.kbRequest.makeGetRequest('/guest/auth/closeActiveSessions').toPromise()
            .then(res => {
                return this.getSessionData().then(() => {
                    return res;
                });
            });
    }

    public generateTwoFactorSettings() {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/twoFactorSettings')
            .toPromise();
    }

    public authenticateNewTwoFactor(code, secret) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/newTwoFactor',
            {
                params: {code, secret}
            }
        ).toPromise().then(user => {
            if (user) {
                this.setSessionUser(user);
            }
            return user;
        });
    }

    public authenticateTwoFactor(code) {
        const url = this.config.guestHttpURL + `/auth/twoFactor?code=${code}`;
        return this.kbRequest.makeGetRequest(url).toPromise()
            .then(result => {
                if (result) {
                    sessionStorage.removeItem('pendingLoginSession');
                    return this.getLoggedInUser();
                } else {
                    throw(result);
                }
            });
    }

    public disableTwoFactor() {
        const url = this.config.accessHttpURL + '/user/disableTwoFA';
        return this.kbRequest.makeGetRequest(url).toPromise().then(user => {
            this.setSessionUser(user);
        });
    }

    public doesUserExist(username: string) {
        return Promise.resolve(true);
    }

    public emailAvailable(emailAddress) {
        return this.kbRequest.makeGetRequest(
            this.config.accessHttpURL + `/auth/emailExists?emailAddress=${emailAddress}`
        ).toPromise().then(res => {
            return !res;
        });
    }

    public getInvitationDetails(invitationCode) {
        return this.kbRequest.makeGetRequest(
            this.config.guestHttpURL + `/registration/invitation/${invitationCode}`
        ).toPromise();
    }

    public acceptInvitation(invitationCode, name = '', password = '', email = '') {
        return this.kbRequest.makePostRequest(
            this.config.guestHttpURL + `/registration/invitation/${invitationCode}`,
            {name, password: this.getHashedPassword(password, email, true)}
        ).toPromise();
    }

    public validateUserPassword(emailAddress, password) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/auth/validatePassword', {
            params: {
                emailAddress,
                password: this.getHashedPassword(password)
            }
        }).toPromise();
    }

    public changeUserDetails(newEmailAddress, newName, password, userId?) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/changeDetails', {
            params: {
                newEmailAddress,
                newName,
                password: this.getHashedPassword(password)
            }
        }).toPromise().then(res => {
            if (res) {
                return this.getLoggedInUser();
            }
        });
    }

    public changeUserName(newName, password) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/changeName', {
            params: {
                newName,
                password: this.getHashedPassword(password)
            }
        }).toPromise().then(res => {
            if (res) {
                return this.getLoggedInUser();
            }
        });
    }

    public changeUserEmailAddress(newEmailAddress, password) {
        const sessionData = this.sessionData.getValue();
        const params: any = {newEmailAddress, password};
        if (sessionData.sessionSalt) {
            params.password = this.getHashedPassword(password);
            params.hashedPassword = sha512.sha512(password + newEmailAddress);
        }
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/changeEmail', {
            params
        }).toPromise().then(res => {
            if (res) {
                return this.getLoggedInUser();
            }
        });
    }

    public changeUserBackEmailAddress(newEmailAddress, password) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/changeBackupEmail', {
            params: {
                newEmailAddress,
                password: this.getHashedPassword(password)
            }
        }).toPromise().then(res => {
            if (res) {
                return this.getLoggedInUser();
            }
        });
    }

    public changeUserMobile(newMobile, password) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/changeMobile', {
            params: {
                newMobile,
                password: this.getHashedPassword(password)
            }
        }).toPromise().then(res => {
            if (res) {
                return this.getLoggedInUser();
            }
        });
    }

    public getGoogleAuthSettings() {
        return Promise.resolve(123);
    }

    public logout() {
        this.authUser.next(null);
        this.sessionData.next(null);
        sessionStorage.clear();
        return this.kbRequest.makeGetRequest(this.config.guestHttpURL + '/auth/logout')
            .toPromise();
    }

    public setSessionUser(user) {
        sessionStorage.setItem('loggedInUser', JSON.stringify(user));
        this.authUser.next(user);
        return Promise.resolve(user);
    }

    public setLoadingRequest(value) {
        this.loadingRequests.next(value);
    }

    public getSessionData() {
        return this.kbRequest.makeGetRequest(this.config.guestHttpURL + '/session')
            .toPromise()
            .then(sessionData => {
                if (sessionData) {
                    sessionStorage.setItem('sessionData', JSON.stringify(sessionData));
                    this.sessionData.next(sessionData);
                    return sessionData;
                } else {
                    sessionStorage.removeItem('sessionData');
                    this.sessionData.next(null);
                    return null;
                }
            });
    }

    public getHashedPassword(password, emailAddress?, newPassword = false) {
        let hashedPassword;
        const sessionData = this.sessionData.getValue();
        const loggedInUser = this.authUser.getValue();

        const email = emailAddress ? emailAddress : (loggedInUser ? loggedInUser.emailAddress : '');

        const hash = sha512.sha512(password + email);
        if (newPassword) {
            return hash;
        }

        if (email && sessionData && sessionData.sessionSalt) {
            hashedPassword = sha512.sha512(hash + sessionData.sessionSalt);
        }
        return hashedPassword || password;
    }
}
