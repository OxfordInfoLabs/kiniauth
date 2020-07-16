import { Component, EventEmitter, Input, OnInit, Output, ViewEncapsulation } from '@angular/core';
import { UserService } from '../../../services/user.service';
import * as _ from 'lodash';

@Component({
    selector: 'ka-edit-roles',
    templateUrl: './edit-roles.component.html',
    styleUrls: ['./edit-roles.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class EditRolesComponent implements OnInit {

    @Input() scope: string;
    @Input() scopeKey: string;
    @Input() user: any;
    @Input() updatedScopes: any;
    @Input() hideApply: boolean;
    @Output() closed: EventEmitter<any> = new EventEmitter<any>();
    @Output() saved: EventEmitter<any> = new EventEmitter<any>();

    public assignableRoles: any[];
    public _ = _;
    public Object = Object;
    public disabled = {};
    public checked = {};
    public loading = true;
    public roles: any[] = [];
    public errors = {};


    constructor(private userService: UserService) {
    }

    ngOnInit() {
        if (!this.updatedScopes) {
            this.updatedScopes = {};
        }
        if (this.scope) {
            const userId = this.user ? this.user.id : null;
            const promises = [
                this.userService.getAssignableRoles(userId, this.scope),
                this.loadUserRoles()
            ];
            Promise.all(promises).then(res => {
                this.assignableRoles = res[0];
                this.setInitialRoleState();
                this.loading = false;
            });
        }
    }

    public updateRole(event, scopeId) {
        // Reset the displayed errors at this point;
        this.errors = {};
        const checked = event.target.checked;
        const owner = event.target.value === 'OWNER';
        let roleIds: any[] = [];

        if (owner) {
            if (checked) {
                roleIds = [null];
            } else {
                roleIds = this.setRoleIds(scopeId);
            }

            this.disabled[scopeId] = checked;
        } else {
            roleIds = this.setRoleIds(scopeId);
        }

        this.updatedScopes[scopeId] = {
            scope: this.scope,
            scopeId,
            roleIds: _.uniq(roleIds)
        };
    }

    public updateUserScope() {
        this.errors = {};
        // Check if we are saving any ACCOUNT scopes. If we are, check there are some roles attached.
        const accounts = _.filter(this.updatedScopes, update => {
            return update.scope === 'ACCOUNT' && update.roleIds.length === 0;
        });
        if (accounts.length > 0) {
            accounts.forEach(scopeUpdate => {
                this.errors[scopeUpdate.scopeId] = 'Please select at least one Account role for this user.';
            });
            return false;
        }

        this.userService.updateUserScope(_.values(this.updatedScopes), this.user.id).then(() => {
            this.closed.emit(true);
        });

    }

    public setInitialRoleState() {
        this.assignableRoles.forEach(assignableRole => {
            if (!this.checked[assignableRole.scopeId]) {
                this.checked[assignableRole.scopeId] = {};
            }

            const allAssignableRoles = _.values(assignableRole.roles);
            const existing = _.find(this.roles, { scopeId: assignableRole.scopeId });
            if (existing && (existing.roles[0] === null && existing.roles.length === 1)) {
                this.checked[assignableRole.scopeId]['owner'] = true;
                this.disabled[assignableRole.scopeId] = true;
            }


            allAssignableRoles.forEach(role => {
                if (role) {
                    const checked = existing ? !!_.find(existing.roles, { id: role.id }) : false;
                    this.checked[assignableRole.scopeId][role.id] = checked;
                }
            });
        });
    }

    private setRoleIds(scopeId) {
        const ids = Object.keys(this.checked[scopeId]);
        const res = ids.filter(id => {
            return this.checked[scopeId][id];
        });
        return res.length ? res.map(Number) : [];
    }

    private loadUserRoles() {
        if (this.user) {
            return this.userService.getAllUserAccountRoles(this.user.id).then(roles => {
                this.roles = roles[this.scopeKey];
                return true;
            });
        }
    }

}
