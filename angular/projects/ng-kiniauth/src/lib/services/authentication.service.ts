import {Injectable} from '@angular/core';
import {KiniAuthModuleConfig} from '../../ng-kiniauth.module';
import {BehaviorSubject} from 'rxjs/internal/BehaviorSubject';
import * as lodash from 'lodash';

const _ = lodash.default;
import * as sha512 from 'js-sha512' ;
import { HttpClient, HttpHeaders } from '@angular/common/http';
import {map} from 'rxjs/operators';

@Injectable({
    providedIn: 'root'
})
export class AuthenticationService {

    public authUser: BehaviorSubject<any> = new BehaviorSubject(null);
    public sessionData: BehaviorSubject<any> = new BehaviorSubject<any>(null);
    public loadingRequests: BehaviorSubject<boolean> = new BehaviorSubject<boolean>(false);

    constructor(private config: KiniAuthModuleConfig,
                private http: HttpClient) {

        const user = sessionStorage.getItem('loggedInUser');
        this.authUser.next(JSON.parse(user));

        const sessionData = sessionStorage.getItem('sessionData');
        if (sessionData && _.filter(JSON.parse(sessionData)).length) {
            this.sessionData.next(JSON.parse(sessionData));
        }
    }

    public async getLoggedInUser(reloadSession?): Promise<any> {
        if (reloadSession || !this.sessionData.getValue()) {
            await this.getSessionData();
        }

        const res = await this.http.get(this.config.accessHttpURL + '/user')
            .toPromise();

        if (res) {
            await this.setSessionUser(res);
            const sessionData = sessionStorage.getItem('sessionData');
            if (sessionData && _.filter(JSON.parse(sessionData)).length) {
                this.sessionData.next(JSON.parse(sessionData));
            } else {
                await this.getSessionData();
            }
            return res;
        }
        return null;
    }

    public login(username: string, password: string, clientTwoFactorData?, recaptcha?) {
        const request = this.config.guestHttpURL + `/auth/login`;

        const headers = new HttpHeaders({'X-CAPTCHA-TOKEN': recaptcha || ''});
        const options: any = {headers};

        return this.http.post(request, {
            emailAddress: username,
            password: this.getHashedPassword(password, username),
            clientTwoFactorData: clientTwoFactorData || null
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

    public loginSSO(state: string, code: string) {
        return this.http.post(this.config.guestHttpURL + '/auth/sso/' + state, JSON.stringify(code)).toPromise();
    }


    public loginWithToken(token: string){
        return this.http.post(this.config.guestHttpURL + '/auth/joinWithToken', '"' + token + '"' ).toPromise();
    }


    public sendPasswordReset(emailAddress, recaptcha?) {
        const headers = new HttpHeaders({'X-CAPTCHA-TOKEN': recaptcha || ''});

        return this.http.post(this.config.guestHttpURL + '/auth/passwordResetRequest',
            JSON.stringify(emailAddress), {
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
        return this.http.post(this.config.accessHttpURL + '/user/changeUserPassword', {
            newPassword: this.getHashedPassword(newPassword, email, true),
            password: this.getHashedPassword(existingPassword, email)
        }).toPromise();
    }

    public unlockUserWithCode(code: string) {
        return this.http.get(this.config.guestHttpURL + '/auth/unlockUser/' + code)
            .toPromise();
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
        return this.http.get('/guest/auth/closeActiveSessions').toPromise()
            .then(res => {
                return this.getSessionData().then(() => {
                    return res;
                });
            });
    }

    public generateTwoFactorSettings() {
        return this.http.get(this.config.accessHttpURL + '/user/twoFactorSettings')
            .toPromise();
    }

    public authenticateNewTwoFactor(code, secret) {
        return this.http.get(this.config.accessHttpURL + '/user/newTwoFactor',
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

    public async authenticateTwoFactor(code) {
        const url = this.config.guestHttpURL + `/auth/twoFactor`;
        const result = await this.http.post(url, JSON.stringify(code)).toPromise();
        if (result) {
            sessionStorage.removeItem('pendingLoginSession');
            await this.getLoggedInUser(true);
            return result;
        } else {
            throw(result);
        }
    }

    public disableTwoFactor() {
        const url = this.config.accessHttpURL + '/user/disableTwoFA';
        return this.http.get(url).toPromise().then(user => {
            this.setSessionUser(user);
        });
    }

    public doesUserExist(username: string) {
        return Promise.resolve(true);
    }

    public emailAvailable(emailAddress) {
        return this.http.get(
            this.config.accessHttpURL + `/auth/emailExists?emailAddress=${emailAddress}`
        ).toPromise().then(res => {
            return !res;
        });
    }

    public getInvitationDetails(invitationCode) {
        return this.http.get(
            this.config.guestHttpURL + `/registration/invitation/${invitationCode}`
        ).toPromise();
    }

    public acceptInvitation(invitationCode, name = '', password = '', email = '') {
        return this.http.post(
            this.config.guestHttpURL + `/registration/invitation/${invitationCode}`,
            {name, password: this.getHashedPassword(password, email, true)}
        ).toPromise();
    }

    public validateUserPassword(emailAddress, password) {
        return this.http.post(this.config.accessHttpURL + '/auth/validatePassword', {
            emailAddress,
            password: this.getHashedPassword(password)
        }).toPromise();
    }

    public changeUserDetails(newEmailAddress, newName, password, userId?) {
        return this.http.post(this.config.accessHttpURL + '/user/changeDetails', {
            newEmailAddress,
            newName,
            password: this.getHashedPassword(password)
        }).toPromise().then(res => {
            if (res) {
                return this.getLoggedInUser();
            }
        });
    }

    public changeUserName(newName, password) {
        return this.http.post(this.config.accessHttpURL + '/user/changeName', {
            newName,
            password: this.getHashedPassword(password)
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
        return this.http.post(this.config.accessHttpURL + '/user/changeEmail', params).toPromise().then(res => {
            if (res) {
                return this.getLoggedInUser();
            }
        });
    }

    public changeUserBackEmailAddress(newEmailAddress, password) {
        return this.http.post(this.config.accessHttpURL + '/user/changeBackupEmail', {
            newEmailAddress,
            password: this.getHashedPassword(password)
        }).toPromise().then(res => {
            if (res) {
                return this.getLoggedInUser();
            }
        });
    }

    public changeUserMobile(newMobile, password) {
        return this.http.post(this.config.accessHttpURL + '/user/changeMobile', {
            newMobile,
            password: this.getHashedPassword(password)
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
        return this.http.get(this.config.guestHttpURL + '/auth/logout')
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

    public sessionTransfer(token) {
        return this.http.post(this.config.guestHttpURL + '/auth/sessionTransfer', '"' + token + '"').toPromise();
    }

    public getSessionData() {
        return this.http.get(this.config.guestHttpURL + '/session')
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
