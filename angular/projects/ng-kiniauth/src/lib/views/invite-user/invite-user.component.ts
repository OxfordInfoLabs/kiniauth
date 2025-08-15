import {Component, Input, OnInit, ViewEncapsulation} from '@angular/core';
import {RoleService} from '../../services/role.service';
import * as lodash from 'lodash';

const _ = lodash.default;
import {AccountService} from '../../services/account.service';
import {Location} from '@angular/common';
import {UserService} from '../../services/user.service';

@Component({
    selector: 'ka-invite-user',
    templateUrl: './invite-user.component.html',
    styleUrls: ['./invite-user.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class InviteUserComponent implements OnInit {

    @Input() defaultToOwner = false;
    @Input() defaultScopeId: number;

    public scopeAccesses: any[];
    public scopeRoles: any = {ACCOUNT: {}};
    public emailAddress: string;
    public accountError: string;
    public inviteComplete = false;
    public inviteError: string = null;
    public securityDomains: any = [];

    public readonly _ = _;

    constructor(private roleService: RoleService,
                private accountService: AccountService,
                private location: Location,
                public userService: UserService) {
    }

    ngOnInit() {
        this.roleService.getScopeAccesses().then(scopeAccesses => {
            delete scopeAccesses['ACCOUNT'];
            delete scopeAccesses['SITE'];
            this.scopeAccesses = _.values(scopeAccesses);
            _.forEach(scopeAccesses, scopeAccess => {
                this.scopeRoles[scopeAccess.scope] = {};
            });
        });

        this.accountService.getAccountSecurityDomains().then(securityDomains => {
            this.securityDomains = securityDomains;
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

        this.inviteError = null;

        this.accountService.inviteUserToAccount(this.emailAddress, scopeRoles).then(() => {
            this.inviteComplete = true;
        }).catch((response) => {
            this.inviteError = response.error.message;
        });
    }

    public back() {
        this.location.back();
    }

}
