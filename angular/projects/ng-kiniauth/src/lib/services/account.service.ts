import {Injectable} from '@angular/core';
import {KiniAuthModuleConfig} from '../../ng-kiniauth.module';
import {AuthenticationService} from './authentication.service';
import * as lodash from 'lodash';
import {HttpClient} from '@angular/common/http';

const _ = lodash.default;

@Injectable({
    providedIn: 'root'
})
export class AccountService {

    constructor(private config: KiniAuthModuleConfig,
                private authService: AuthenticationService,
                private http: HttpClient) {
    }

    public getAccount(accountId?) {
        const accountString = accountId ? `/${accountId}` : '';
        return this.http.get(this.config.accessHttpURL + '/account' + accountString).toPromise()
            .catch(err => {
                return null;
            });
    }

    public searchForAccounts(searchString?, limit?, offset?) {
        return this.http.get(this.config.accessHttpURL + '/account', {
            params: _.pickBy({searchString, limit, offset}, _.identity)
        });
    }

    public createAccount(accountName, emailAddress = null, rawPassword = null, name = null) {
        let password = rawPassword;
        if (rawPassword) {
            password = this.authService.getHashedPassword(rawPassword, emailAddress, true);
        }

        return this.http.post(this.config.accessHttpURL + '/account',
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

    public inviteUserToAccount(emailAddress, assignedRoles) {
        return this.http.post(this.config.accessHttpURL + '/account/invite?emailAddress=' + emailAddress,
            assignedRoles).toPromise();
    }


    public async getAccountDiscoverabilitySettings(){
        const settings = await this.http.get(this.config.accessHttpURL + '/account/discovery')
            .toPromise();
        return (!settings || Array.isArray(settings)) ? {} : settings;
    }


    public async setAccountDiscoverable(discoverable){
        return this.http.put(this.config.accessHttpURL + '/account/discoverable',
            discoverable ? 1 : 0).toPromise();
    }


    public async generateAccountExternalIdentifier(){
        return this.http.put(this.config.accessHttpURL + '/account/externalIdentifier',
            null).toPromise();
    }

    public async unsetAccountExternalIdentifier(){
        return this.http.delete(this.config.accessHttpURL + '/account/externalIdentifier').toPromise();
    }

}
