import {Component, Input, OnInit, ViewEncapsulation} from '@angular/core';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import * as lodash from 'lodash';

const _ = lodash.default;
import {UserService} from '../../../services/user.service';
import {Router} from '@angular/router';
import {AuthenticationService} from '../../../services/authentication.service';
import {AccountService} from '../../../services/account.service';
import {MatSnackBar} from '@angular/material/snack-bar';

@Component({
    selector: 'ka-account-users',
    templateUrl: './account-users.component.html',
    styleUrls: ['./account-users.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class AccountUsersComponent implements OnInit {

    @Input() userRoleRoute: string;
    @Input() inviteUserRoute: string;
    @Input() disableInvite: boolean;
    @Input() createAdminUser: boolean;

    public users: any[];
    public invitations: any;
    public searchText = new BehaviorSubject<string>('');
    public limit = new BehaviorSubject<number>(10);
    public offset = new BehaviorSubject<number>(0);
    public pageIndex = 0;
    public resultSize = 0;
    public reloadUsers = new Subject();
    public allSelected = false;
    public selectionMade = false;
    public lodash = _;
    public passwordReset = false;
    public userUnlocked = false;
    public userSuspended = false;
    public newAdminUser = false;
    public newAdminEmail = '';
    public newAdminName = '';
    public newAdminPassword: string = null;
    public newAdminAdded = false;

    constructor(private userService: UserService,
                private accountService: AccountService,
                private router: Router,
                private authService: AuthenticationService,
                private matSnackbar: MatSnackBar) {
    }

    ngOnInit() {
        merge(this.searchText, this.limit, this.offset, this.reloadUsers)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getUsers()
                )
            )
            .subscribe((users: any) => {
                this.users = users;
            });

        this.accountService.getActiveAccountInvitations().then(invitations => {
            this.invitations = invitations;
        });

    }

    public saveNewAdminUser() {
        if (this.newAdminPassword && this.newAdminPassword.length < 8) {
            return true;
        }

        return this.userService.createAdminUser(this.newAdminEmail, this.newAdminPassword || null, this.newAdminName || null)
            .then(res => {
                this.newAdminEmail = '';
                this.newAdminName = '';
                this.newAdminPassword = null;
                this.newAdminUser = false;
                this.newAdminAdded = true;
                this.reloadUsers.next(Date.now());
                setTimeout(() => {
                    this.newAdminAdded = false;
                }, 3000);
            });
    }

    public viewUser(user) {
        if (user.status !== 'PENDING') {
            const route = this.userRoleRoute ? this.userRoleRoute + '/' + user.id : user.id;
            this.router.navigate([route]);
        }
    }

    public toggleSelectAllUsers() {
        this.allSelected = !this.allSelected;
        this.selectionMade = this.allSelected;
        this.users = this.lodash.map(this.users, user => {
            user.selected = this.allSelected;
            return user;
        });
    }

    public toggleUsersSelected(user) {
        user.selected = !user.selected;
        this.selectionMade = this.lodash.some(this.users, 'selected');
    }

    public search(searchTerm: string) {
        this.searchText.next(searchTerm);
    }

    public updatePage(pageEvent) {
        const limit = this.limit.getValue();

        if (pageEvent.pageSize !== limit) {
            this.offset.next(0);
            this.limit.next(pageEvent.pageSize);
            this.pageIndex = 0;
        } else {
            this.offset.next(pageEvent.pageSize * (pageEvent.pageIndex));
            this.pageIndex = pageEvent.pageIndex;
        }
    }

    public removeUser(user) {
        const message = 'Are you sure you would like to remove this user?';
        if (window.confirm(message)) {
            this.userService.removeUserFromAccount(user.id).then(() => {
                this.reloadUsers.next(Date.now());
            });
        }
    }

    public resetPassword(user) {
        this.authService.sendPasswordReset(user.emailAddress, null).then(() => {
            this.passwordReset = true;
            setTimeout(() => {
                this.passwordReset = false;
            }, 3000);
        });
    }

    public unlockUser(userId) {
        this.userService.unlockUser(userId).then(() => {
            this.userUnlocked = true;
            this.reloadUsers.next(Date.now());
            setTimeout(() => {
                this.userUnlocked = false;
            }, 3000);
        });
    }

    public suspendUser(userId) {
        this.userService.suspendUser(userId).then(() => {
            this.userSuspended = true;
            this.reloadUsers.next(Date.now());
            setTimeout(() => {
                this.userSuspended = false;
            }, 3000);
        });
    }


    // Resend invitation
    public resendInvitation(emailAddress) {
        this.accountService.resendActiveAccountInvitationEmail(emailAddress).then(() => {
            this.matSnackbar.open('Invitation resent to ' + emailAddress, null, {
                verticalPosition: 'top',
                duration: 3000,
                panelClass: 'bg-gray-100'
            });
        });
    }


    private getUsers() {
        return this.userService.getAccountUsers(
            this.searchText.getValue(),
            this.limit.getValue(),
            this.offset.getValue()
        ).pipe(map((data: any) => {
            this.resultSize = data.totalRecords;
            return data.results;
        }));
    }

}
