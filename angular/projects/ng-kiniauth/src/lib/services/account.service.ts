import { Injectable } from '@angular/core';
import { KinibindRequestService } from 'ng-kinibind';
import { KiniAuthModuleConfig } from '../../ng-kiniauth.module';
import { AuthenticationService } from './authentication.service';
import * as lodash from 'lodash';
const _ = lodash.default;

@Injectable({
    providedIn: 'root'
})
export class AccountService {

    constructor(private kbRequest: KinibindRequestService,
                private config: KiniAuthModuleConfig,
                private authService: AuthenticationService) {
    }

    public getAccount(accountId?) {
        const accountString = accountId ? `/${accountId}` : '';
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/account' + accountString).toPromise()
            .catch(err => {
                return null;
            });
    }

    public searchForAccounts(searchString?, limit?, offset?) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/account', {
            params: _.pickBy({searchString, limit, offset}, _.identity)
        });
    }

    public createAccount(accountName, emailAddress = null, rawPassword = null, name = null) {
        let password = rawPassword;
        if (rawPassword) {
            password = this.authService.getHashedPassword(rawPassword, emailAddress, true);
        }

        return this.kbRequest.makePostRequest(this.config.accessHttpURL + '/account',
            _.omitBy({accountName, emailAddress, password, name}, _.isNil)
        ).toPromise();
    }

    public suspendAccount(accountId, note) {
        return this.kbRequest.makeRequest('PUT',
            this.config.accessHttpURL + '/account/' + accountId + '/suspend', {
                params: {note}
            })
            .toPromise();
    }

    public updateAccountName(accountId, newAccountName) {
        return this.kbRequest.makeRequest('PUT',
            this.config.accessHttpURL + '/account/' + accountId + '/name', {
                params: {newAccountName}
            })
            .toPromise();
    }

    public reactivateAccount(accountId, note) {
        return this.kbRequest.makeRequest('PUT',
            this.config.accessHttpURL + '/account/' + accountId + '/reactivate', {
                params: {note}
            })
            .toPromise();
    }

    public changeAccountName(newName, password) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/account/changeName', {
            params: {
                newName,
                password: this.authService.getHashedPassword(password)
            }
        }).toPromise().then(res => {
            if (res) {
                return true;
            }
        });
    }

    public inviteUserToAccount(emailAddress, assignedRoles) {
        return this.kbRequest.makePostRequest(this.config.accessHttpURL + '/account/invite?emailAddress=' + emailAddress,
            assignedRoles).toPromise();
    }

}
