import { Component, OnInit, ViewEncapsulation } from '@angular/core';
import { UserService } from '../../services/user.service';
import { ActivatedRoute } from '@angular/router';
import * as _ from 'lodash';
import { AuthenticationService } from '../../services/authentication.service';
import {RoleService} from '../../services/role.service';

@Component({
    selector: 'ka-user-roles',
    templateUrl: './user-roles.component.html',
    styleUrls: ['./user-roles.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class UserRolesComponent implements OnInit {

    public accountError: string;
    public user: any = {};
    public Object = Object;
    public editDetails = false;
    public loggedInUser: any;
    public scopeAccesses: any[];
    public scopeRoles: any = { ACCOUNT: {} };
    public editRoles = false;
    public scopeEdit = null;
    public scopeKey: string;

    private userId;

    constructor(private userService: UserService,
                private route: ActivatedRoute,
                private roleService: RoleService,
                public authService: AuthenticationService) {
    }

    ngOnInit() {
        this.route.params.subscribe(params => {
            this.loadUser();
            this.userId = params.userId;
        });

        this.loadUser();
    }

    public saveUserDetails() {

    }

    private loadRoles(userId) {
        this.userService.getUser(userId).then(user => {
            this.user = user;
        });
        this.roleService.getScopeAccesses().then(scopeAccesses => {
            delete scopeAccesses.ACCOUNT;
            this.scopeAccesses = _.values(scopeAccesses);
            _.forEach(scopeAccesses, scopeAccess => {
                this.scopeRoles[scopeAccess.scope] = {};
            });
        });
    }

    private loadUser() {
        this.authService.getLoggedInUser().then(user => {
            this.loggedInUser = user;
            this.loadRoles(this.userId);
        });
    }
}
