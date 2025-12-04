import {Injectable} from '@angular/core';
import {KiniAuthModuleConfig} from '../../ng-kiniauth.module';
import {HttpClient} from '@angular/common/http';
import {AuthenticationService} from './authentication.service';
import * as lodash from 'lodash';

const _ = lodash.default;

@Injectable({
    providedIn: 'root'
})
export class GroupService {

    constructor(private config: KiniAuthModuleConfig,
                private authService: AuthenticationService,
                private http: HttpClient) {
    }

    public async getGroup(groupId: number) {
        const group: any = await this.http.get(this.config.accessHttpURL + '/accountGroup', {
            params: {accountGroupId: groupId}
        }).toPromise();

        const session = this.authService.sessionData.getValue();
        group.owner = group.ownerAccountId === session.account.accountId;

        return group;
    }

    public async listAccountGroups() {
        const session = this.authService.sessionData.getValue();
        const accountGroups = await this.http.get(this.config.accessHttpURL + '/accountGroup/list').toPromise();
        return _(accountGroups || {}).values().map((group: any) => {
            group.owner = group.ownerAccountId === session.account.accountId;
            group.feeds = [];
            return group;
        }).valueOf();
    }

    public createAccountGroup(name: string, description: string) {
        const session = this.authService.sessionData.getValue();
        return this.http.post(this.config.accessHttpURL + '/accountGroup/new', {
            name, description, ownerAccountId: session.account.accountId
        }).toPromise();
    }

    public deleteGroup(accountGroupId: number) {
        return this.http.delete(this.config.accessHttpURL + '/accountGroup', {
            params: {accountGroupId}
        }).toPromise();
    }

    public leaveGroup(accountGroupId: number) {
        return this.http.get(this.config.accessHttpURL + '/accountGroup/leave', {
            params: {accountGroupId}
        }).toPromise();
    }

    public inviteAccountToGroup(accountGroupId: number, accountExternalIdentifier: number) {
        return this.http.get(this.config.accessHttpURL + '/accountGroup/invite', {
            params: {accountGroupId, accountExternalIdentifier}
        }).toPromise();
    }

    public removeAccountFromGroup(accountGroupId: number, accountId: number) {
        return this.http.get(this.config.accessHttpURL + '/accountGroup/removeMember', {
            params: {accountGroupId, accountId}
        }).toPromise();
    }

    public getGroupInvitations(accountGroupId: number) {
        return this.http.get(this.config.accessHttpURL + '/accountGroup/invitations', {
            params: {accountGroupId}
        }).toPromise();
    }

    public revokeGroupInvitation(accountGroupInvitation: any) {
        return this.http.delete(this.config.accessHttpURL + '/accountGroup/invite', {
            body: accountGroupInvitation
        }).toPromise();
    }

    public getInvitationDetails(invitationCode: string) {
        return this.http.get(this.config.guestHttpURL + '/accountGroup/invitation/' + invitationCode)
            .toPromise();
    }

    public acceptInvitation(invitationCode: string) {
        return this.http.post(this.config.guestHttpURL + '/accountGroup/invitation/' + invitationCode, {})
            .toPromise();
    }
}
