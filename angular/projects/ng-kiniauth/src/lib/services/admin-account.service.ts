import {Injectable} from '@angular/core';
import {KiniAuthModuleConfig} from '../../ng-kiniauth.module';
import {AuthenticationService} from './authentication.service';
import { HttpClient } from '@angular/common/http';
import * as lodash from 'lodash';

const _ = lodash.default;

@Injectable({
    providedIn: 'root'
})
export class AdminAccountService {

    constructor(private config: KiniAuthModuleConfig,
                private authService: AuthenticationService,
                private http: HttpClient) {
    }

    public getAccount(accountId: number) {
        return this.http.get(this.config.accessHttpURL + '/account/' + accountId).toPromise()
            .catch(err => {
                return null;
            });
    }

    public searchForAccounts(filters?, limit?, offset?) {
        return this.http.post(this.config.accessHttpURL + '/account/search?limit=' + limit + '&offset=' + offset, filters);
    }

    public searchForSubAccounts(searchString?, limit?, offset?) {
        return this.http.get(this.config.accessHttpURL + '/account/subAccounts', {
            params: _.pickBy({searchString, limit, offset}, _.identity)
        });
    }

    public createAccount(accountName: string, emailAddress: string = null, rawPassword: string = null, name: string = null) {
        let password = rawPassword;
        if (rawPassword) {
            password = this.authService.getHashedPassword(rawPassword, emailAddress, true);
        }

        return this.http.post(this.config.accessHttpURL + '/account',
            _.omitBy({accountName, emailAddress, password, name}, _.isNil)
        ).toPromise();
    }

    public createSubAccount(accountName: string, emailAddress: string = null, rawPassword: string = null, name: string = null) {
        let password = rawPassword;
        if (rawPassword) {
            password = this.authService.getHashedPassword(rawPassword, emailAddress, true);
        }

        return this.http.post(this.config.accessHttpURL + '/account/subAccount',
            _.omitBy({accountName, emailAddress, password, name}, _.isNil)
        ).toPromise();
    }

    public suspendAccount(accountId, note) {
        return this.http.put(
            this.config.accessHttpURL + '/account/' + accountId + '/suspend', {
                params: {note}
            })
            .toPromise();
    }

    public async getAccountSettings() {
        const settings = await this.http.get(this.config.accessHttpURL + '/account/settings')
            .toPromise();
        return (!settings || Array.isArray(settings)) ? {} : settings;
    }

    public updateAccountSettings(settings) {
        return this.http.put(this.config.accessHttpURL + '/account/settings', settings)
            .toPromise();
    }

    public updateAccountName(accountId, newAccountName) {
        return this.http.put(
            this.config.accessHttpURL + '/account/' + accountId + '/name', {
                params: {newAccountName}
            })
            .toPromise();
    }

    public reactivateAccount(accountId, note) {
        return this.http.put(
            this.config.accessHttpURL + '/account/' + accountId + '/reactivate', {
                params: {note}
            })
            .toPromise();
    }

    public changeAccountName(newName, password) {
        return this.http.post(this.config.accessHttpURL + '/account/changeName', {
            newName,
            password: this.authService.getHashedPassword(password)
        }).toPromise().then(res => {
            if (res) {
                return true;
            }
        });
    }

    public removeUserFromAccount(accountId, userId) {
        return this.http.get(this.config.accessHttpURL + '/account/removeUser', {
            params: {accountId, userId}
        }).toPromise();
    }

    public inviteUserToAccount(emailAddress, assignedRoles, accountId = null) {
        let url = this.config.accessHttpURL + '/account/invite?emailAddress=' + encodeURIComponent(emailAddress);
        if (accountId) {
            url = `${url}&accountId=${accountId}`;
        }
        return this.http.post(url,
            assignedRoles).toPromise();
    }

    public async getAccountInvitations(accountId: number) {
        return await this.http.get(this.config.accessHttpURL + '/account/invitations', {
            params: {accountId}
        }).toPromise();
    }

    public resendActiveAccountInvitationEmail(accountId, emailAddress) {
        const url = this.config.accessHttpURL + '/account/invite?accountId=' + accountId;
        return this.http.put(url, '"' + encodeURIComponent(emailAddress) + '"').toPromise();
    }


    public async getAccountDiscoverabilitySettings() {
        const settings = await this.http.get(this.config.accessHttpURL + '/account/discovery')
            .toPromise();
        return (!settings || Array.isArray(settings)) ? {} : settings;
    }


    public async setAccountDiscoverable(discoverable) {
        return this.http.put(this.config.accessHttpURL + '/account/discoverable',
            discoverable ? 1 : 0).toPromise();
    }


    public async getAccountSecurityDomains(accountId: number) {
        return await this.http.get(this.config.accessHttpURL + '/account/' + accountId + '/securityDomains').toPromise();
    }

    public async updateAccountSecurityDomains(accountId, securityDomains) {
        return await this.http.put(this.config.accessHttpURL + '/account/' + accountId + '/securityDomains', securityDomains).toPromise();
    }

    public async searchForDiscoverableAccounts(searchTerm, offset = 0, limit = 25) {
        return await this.http.get(this.config.accessHttpURL + '/account/discoverable?searchTerm=' + searchTerm + '&limit=' + limit + '&offset=' + offset).toPromise();
    }


    public async lookupDiscoverableAccountByExternalIdentifier(externalIdentifier) {
        return await this.http.get(this.config.accessHttpURL + '/account/discoverable/' + externalIdentifier).toPromise();
    }

    public async generateAccountExternalIdentifier() {
        return this.http.put(this.config.accessHttpURL + '/account/externalIdentifier',
            null).toPromise();
    }

    public async unsetAccountExternalIdentifier() {
        return this.http.delete(this.config.accessHttpURL + '/account/externalIdentifier').toPromise();
    }

    public async generateJoinAccountToken(accountId: number){
        return await this.http.get(this.config.accessHttpURL + '/auth/joinAccountToken/' + accountId).toPromise();
    }

}
