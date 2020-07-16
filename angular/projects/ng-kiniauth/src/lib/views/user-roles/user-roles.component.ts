import { Component, OnInit, ViewEncapsulation } from '@angular/core';
import { UserService } from '../../services/user.service';
import { ActivatedRoute } from '@angular/router';
import * as _ from 'lodash';
import { AuthenticationService } from '../../services/authentication.service';

@Component({
    selector: 'ka-user-roles',
    templateUrl: './user-roles.component.html',
    styleUrls: ['./user-roles.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class UserRolesComponent implements OnInit {

    public userRoles: any = {};
    public user: any = {};
    public Object = Object;
    public editDetails = false;
    public loggedInUser: any;
    public userId: number;

    public editRoles = false;
    public scopeEdit = null;
    public scopeKey: string;

    constructor(private userService: UserService,
                private route: ActivatedRoute,
                public authService: AuthenticationService) {
    }

    ngOnInit() {
        this.route.params.subscribe(params => {
            this.authService.getLoggedInUser().then(user => {
                this.loggedInUser = user;
                this.loadRoles(params.userId);
                this.userId = params.userId;
            });
        });
    }

    public roleDisplayString(scope) {
        if (scope.roles.length === 1 && scope.roles[0] === null) {
            return 'Owner';
        }
        const strings = _.map(scope.roles, 'name');
        return strings.join(', ');
    }

    public editRolesForScope(roles, scopeKey) {
        if (roles.length) {
            this.scopeEdit = roles[0].scope;
            this.scopeKey = scopeKey;
        }
        this.editRoles = true;
    }

    public closeEditRoles(reload?) {
        this.editRoles = false;
        if (reload) {
            this.loadRoles(this.user.id);
        }
    }

    public closeEditDetails() {
        this.loadRoles(this.userId);
        this.editDetails = false;
    }

    public saveUserDetails() {

    }

    public userCanEditRoles(scopeKey) {
        return scopeKey === 'Account' ? (this.loggedInUser.id !== this.user.id) : true;
    }

    private loadRoles(userId) {
        this.userService.getUser(userId).then(user => {
            this.user = user;
        });
        this.userService.getAllUserAccountRoles(userId).then(roles => {
            this.userRoles = roles;
        });
    }
}
