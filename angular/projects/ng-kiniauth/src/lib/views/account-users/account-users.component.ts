import { Component, Input, OnInit, ViewEncapsulation } from '@angular/core';
import { debounceTime, distinctUntilChanged, map, switchMap } from 'rxjs/operators';
import { BehaviorSubject, merge, Subject } from 'rxjs';
import * as _ from 'lodash';
import * as moment from 'moment';
import { UserService } from '../../services/user.service';
import { Router } from '@angular/router';

@Component({
    selector: 'ka-account-users',
    templateUrl: './account-users.component.html',
    styleUrls: ['./account-users.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class AccountUsersComponent implements OnInit {

    @Input() userRoleRoute: string;

    public users: any[];
    public searchText = new BehaviorSubject<string>('');
    public pageSize = new BehaviorSubject<number>(10);
    public page = new BehaviorSubject<number>(0);
    public reloadUsers = new Subject();
    public allSelected = false;
    public selectionMade = false;
    public usersSize: number;
    public lodash = _;

    private moment = moment;

    constructor(private userService: UserService,
                private router: Router) {
    }

    ngOnInit() {
        merge(this.searchText, this.pageSize, this.page, this.reloadUsers)
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
        const pageSize = this.pageSize.getValue();

        if (pageEvent.pageSize !== pageSize) {
            this.pageSize.next(pageEvent.pageSize);
        } else {
            this.page.next(pageEvent.pageIndex + 1);
        }
    }

    public removeUser(user) {
        const message = 'Are you sure you would like to remove this user?';
        if (window.confirm(message)) {
            this.userService.removeUserFromAccount(user.id).then(() => {
                this.reloadUsers.next(this.moment().unix())
            });
        }
    }

    private getUsers() {
        return this.userService.getAccountUsers(
            this.searchText.getValue(),
            this.pageSize.getValue(),
            this.pageSize.getValue() * this.page.getValue()
        ).pipe(map((data: any) => {
            this.usersSize = data.totalRecords;
            return data.results;
        }));
    }

}
