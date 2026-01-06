import {Injectable} from '@angular/core';
import {KiniAuthModuleConfig} from '../../ng-kiniauth.module';
import * as lodash from 'lodash';
const _ = lodash.default;
import {AuthenticationService} from './authentication.service';
import { HttpClient } from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class UserService {

    constructor(private http: HttpClient,
                private config: KiniAuthModuleConfig,
                private authService: AuthenticationService) {
    }

    public getUser(userId) {
        return this.http.get(this.config.accessHttpURL + '/user/summary', {
            params: {userId}
        }).toPromise();
    }

    public createAdminUser(emailAddress, rawPassword?, name?) {
        let password = rawPassword;
        if (rawPassword) {
            password = this.authService.getHashedPassword(rawPassword, emailAddress, true);
        }

        return this.http.post(this.config.accessHttpURL + '/user/admin',
            _.omitBy({emailAddress, password, name}, _.isNil)
        ).toPromise();
    }

    public getUserExtended(userId) {
        return this.http.get(this.config.accessHttpURL + '/user', {
            params: {userId}
        }).toPromise();
    }

    public getAccountUsers(searchString?, limit?, offset?, accountId?) {
        return this.http.get(this.config.accessHttpURL + '/user/search', {
            params: _.pickBy({searchString, limit, offset, accountId}, _.identity)
        });
    }

    public getAdminUsers(searchString?, limit?, offset?) {
        return this.http.get(this.config.accessHttpURL + '/user/adminSearch', {
            params: _.pickBy({searchString, limit, offset}, _.identity)
        });
    }

    public getAllUserAccountRoles(userId) {
        return this.http.get(this.config.accessHttpURL + '/user/roles', {
            params: {userId}
        }).toPromise();
    }

    public getAssignableRoles(userId, scope, filterString = '', offset = 0, limit = 10) {
        return this.http.get(this.config.accessHttpURL + '/user/assignableRoles', {
            params: {userId, scope, filterString, offset, limit}
        }).toPromise();
    }

    public updateUserScope(scopeObjects, userId) {
        return this.http.post(this.config.accessHttpURL + `/user/updateUserScope?userId=${userId}`,
            scopeObjects).toPromise();
    }

    public removeUserFromAccount(userId) {
        return this.http.get(this.config.accessHttpURL + '/account/removeUser', {
            params: {userId}
        }).toPromise();
    }

    public requestPasswordReset(emailAddress) {
        return this.http.get(this.config.accessHttpURL + '/user/passwordReset', {
            params: {emailAddress}
        }).toPromise();
    }

    public unlockUser(userId) {
        return this.http.get(this.config.accessHttpURL + '/user/unlock', {
            params: {userId}
        }).toPromise();
    }

    public suspendUser(userId) {
        return this.http.get(this.config.accessHttpURL + '/user/suspend', {
            params: {userId}
        }).toPromise();
    }

    public getAccounts(userId) {
        return this.http.get(this.config.accessHttpURL + '/user/accounts', {
            params: {userId}
        }).toPromise();
    }

    public switchAccount(accountId, userId) {
        return this.http.get(this.config.accessHttpURL + '/user/switchAccount', {
            params: {accountId, userId}
        }).toPromise();
    }
}
