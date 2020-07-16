import { Injectable } from '@angular/core';
import { KiniAuthModuleConfig } from '../../ng-kiniauth.module';
import { KinibindRequestService } from 'ng-kinibind';
import { BehaviorSubject } from 'rxjs/internal/BehaviorSubject';
import * as _ from 'lodash';
import * as sha512 from 'js-sha512' ;

@Injectable({
    providedIn: 'root'
})
export class AuthenticationService {

    public authUser: BehaviorSubject<any> = new BehaviorSubject(null);
    public sessionData: BehaviorSubject<any> = new BehaviorSubject<any>(null);
    public loadingRequests: BehaviorSubject<boolean> = new BehaviorSubject<boolean>(false);

    constructor(private kbRequest: KinibindRequestService,
                private config: KiniAuthModuleConfig) {

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

    public login(username: string, password: string) {
        const request = this.config.guestHttpURL + `/auth/login?emailAddress=${username}&password=${password}`;
        return this.kbRequest.makeGetRequest(request).toPromise().then((user: any) => {
            if (user === 'REQUIRES_2FA') {
                return user;
            } else {
                return this.getSessionData().then(() => {
                    return this.setSessionUser(user);
                });
            }
        });
    }

    public generateTwoFactorSettings() {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/twoFactorSettings')
            .toPromise()
    }

    public authenticateNewTwoFactor(code, secret) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/newTwoFactor',
            {
                params: { code, secret }
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

    private getSessionData() {
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

    private getHashedPassword(password) {
        let hashedPassword;
        const sessionData = this.sessionData.getValue();
        const loggedInUser = this.authUser.getValue();
        if (loggedInUser && sessionData && sessionData.sessionSalt) {
            const hash = sha512.sha512(password + loggedInUser.emailAddress);
            hashedPassword = sha512.sha512(hash + sessionData.sessionSalt);
        }
        return hashedPassword || password;
    }
}
