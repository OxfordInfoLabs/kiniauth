import { Component, OnInit, ViewEncapsulation } from '@angular/core';
import { RoleService } from '../../services/role.service';
import * as _ from 'lodash';
import { AccountService } from '../../services/account.service';
import { Location } from '@angular/common';

@Component({
    selector: 'ka-invite-user',
    templateUrl: './invite-user.component.html',
    styleUrls: ['./invite-user.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class InviteUserComponent implements OnInit {

    public scopeAccesses: any[];
    public scopeRoles: any = { ACCOUNT: {} };
    public emailAddress: string;
    public accountError: string;
    public inviteComplete = false;

    constructor(private roleService: RoleService,
                private accountService: AccountService,
                private location: Location) {
    }

    ngOnInit() {
        this.roleService.getScopeAccesses().then(scopeAccesses => {
            delete scopeAccesses['ACCOUNT'];
            this.scopeAccesses = _.values(scopeAccesses);
            _.forEach(scopeAccesses, scopeAccess => {
                this.scopeRoles[scopeAccess.scope] = {};
            });
        });
    }

    public save() {
        this.accountError = '';
        const accounts = _.filter(this.scopeRoles['ACCOUNT'], update => {
            return update.scope === 'ACCOUNT' && update.roleIds.length === 0;
        });
        if (!_.values(this.scopeRoles['ACCOUNT']).length || accounts.length > 0) {
            this.accountError = 'Please select at least one Account role for this user.';
            return false;
        }

        const scopeRoles = [];
        _.forEach(this.scopeRoles, (allRoles, scope) => {
            _.forEach(allRoles, role => {
                scopeRoles.push(role);
            });
        });
        this.accountService.inviteUserToAccount(this.emailAddress, scopeRoles).then(() => {
            this.inviteComplete = true;
        });
    }

    public back() {
        this.location.back();
    }

}
