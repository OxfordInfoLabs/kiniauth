import { Injectable } from '@angular/core';
import { KinibindRequestService } from 'ng-kinibind';
import { KiniAuthModuleConfig } from '../../ng-kiniauth.module';
import * as _ from 'lodash';

@Injectable({
    providedIn: 'root'
})
export class UserService {

    constructor(private kbRequest: KinibindRequestService,
                private config: KiniAuthModuleConfig) {
    }

    public getUser(userId) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/summary', {
            params: { userId }
        }).toPromise();
    }

    public getUserExtended(userId) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user', {
            params: { userId }
        }).toPromise();
    }

    public getAccountUsers(searchString?, limit?, offset?) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/search', {
            params: _.pickBy({ searchString, limit, offset }, _.identity)
        });
    }

    public getAllUserAccountRoles(userId) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/roles', {
            params: { userId }
        }).toPromise();
    }

    public getAssignableRoles(userId, scope, filterString = '', offset = 0, limit = 10) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/assignableRoles', {
            params: { userId, scope, filterString, offset, limit }
        }).toPromise();
    }

    public updateUserScope(scopeObjects, userId) {
        return this.kbRequest.makePostRequest(this.config.accessHttpURL + `/user/updateUserScope?userId=${userId}`,
            scopeObjects).toPromise();
    }

    public removeUserFromAccount(userId) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/account/removeUser', {
            params: { userId }
        }).toPromise();
    }

    public requestPasswordReset(emailAddress) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/passwordReset', {
            params: { emailAddress }
        }).toPromise();
    }

    public unlockUser(userId) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/unlock', {
            params: { userId }
        }).toPromise();
    }

    public suspendUser(userId) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/suspend', {
            params: { userId }
        }).toPromise();
    }

    public getAccounts(userId) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/accounts', {
            params: { userId }
        }).toPromise();
    }

    public switchAccount(accountId, userId) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/user/switchAccount', {
            params: { accountId, userId }
        }).toPromise();
    }
}
