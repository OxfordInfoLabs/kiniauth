import {Component, Input, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {AccountService} from '../../services/account.service';
import {Router} from '@angular/router';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'ka-accounts',
    templateUrl: './accounts.component.html',
    styleUrls: ['./accounts.component.sass']
})
export class AccountsComponent implements OnInit {

    @Input() accountClickThrough: string;

    public accounts: any[];
    public searchText = new BehaviorSubject<string>('');
    public limit = new BehaviorSubject<number>(100);
    public offset = new BehaviorSubject<number>(0);
    public pageIndex = 0;
    public resultSize = 0;
    public reloadAccounts = new Subject();
    public allSelected = false;
    public selectionMade = false;
    public lodash = _;
    public passwordReset = false;
    public accountUnlocked = false;
    public accountSuspended = false;
    public newAccount = false;
    public newAccountEmail = '';
    public newAccountName = '';
    public newUserName = '';
    public newAccountPassword: string = null;
    public newAccountAdded = false;

    constructor(private accountService: AccountService,
                private router: Router) {
    }

    ngOnInit() {
        merge(this.searchText, this.limit, this.offset, this.reloadAccounts)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getAccounts()
                )
            )
            .subscribe((accounts: any) => {
                this.accounts = accounts;
            });
    }

    public accountAction(account) {
        if (this.accountClickThrough) {
            this.router.navigate([this.accountClickThrough, account.accountId]);
        }
    }

    public saveNewAdminUser() {
        if (this.newAccountPassword && this.newAccountPassword.length < 8) {
            return true;
        }

        return this.accountService.createAccount(this.newAccountName, this.newAccountEmail, this.newAccountPassword || null, this.newUserName || null)
            .then(res => {
                this.newAccountName = '';
                this.newAccountEmail = '';
                this.newUserName = '';
                this.newAccountPassword = null;
                this.newAccount = false;
                this.newAccountAdded = true;
                this.reloadAccounts.next(Date.now());
                setTimeout(() => {
                    this.newAccountAdded = false;
                }, 3000);
            });
    }

    public viewAccount(user) {

    }

    public toggleSelectAllUsers() {
        this.allSelected = !this.allSelected;
        this.selectionMade = this.allSelected;
        this.accounts = this.lodash.map(this.accounts, user => {
            user.selected = this.allSelected;
            return user;
        });
    }

    public toggleUsersSelected(user) {
        user.selected = !user.selected;
        this.selectionMade = this.lodash.some(this.accounts, 'selected');
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

    public reactivate(account, note) {
        this.accountService.reactivateAccount(account.accountId, note).then(() => {
            this.accountUnlocked = true;
            this.reloadAccounts.next(Date.now());
            setTimeout(() => {
                this.accountUnlocked = false;
            }, 3000);
        });
    }

    public suspend(account, note) {
        this.accountService.suspendAccount(account.accountId, note).then(() => {
            this.accountSuspended = true;
            this.reloadAccounts.next(Date.now());
            setTimeout(() => {
                this.accountSuspended = false;
            }, 3000);
        });
    }

    private getAccounts() {
        return this.accountService.searchForAccounts(
            this.searchText.getValue(),
            this.limit.getValue(),
            this.offset.getValue()
        ).pipe(map((data: any) => {
            this.resultSize = data.length;
            return data;
        }));
    }

}
