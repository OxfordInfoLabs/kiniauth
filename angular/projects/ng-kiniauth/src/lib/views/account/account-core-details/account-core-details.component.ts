import {Component, EventEmitter, Input, OnDestroy, OnInit, Output, ViewEncapsulation} from '@angular/core';

@Component({
    selector: 'ka-account-core-details',
    templateUrl: './account-core-details.component.html',
    styleUrls: ['./account-core-details.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class AccountCoreDetailsComponent implements OnInit, OnDestroy {

    @Input() authService;
    @Input() accountService;

    @Output('saved') saved: EventEmitter<any> = new EventEmitter();

    public newName = '';
    public currentPassword = '';
    public saveError: string;
    public user: any;

    constructor() {
    }

    ngOnInit() {
        return this.authService.getLoggedInUser().then(user => {
            this.user = user;
        });
    }

    ngOnDestroy(): void {

    }

    public saveNewName() {
        this.saveError = '';
        this.accountService.changeAccountName(this.newName, this.currentPassword)
            .then(success => {
                if (!success){
                    this.saveError = 'Invalid password supplied.';
                } else {
                    this.saved.emit(this.user);
                }
            })
            .catch(err => {
                this.saveError = 'There was a problem changing the account name, please check and try again.';
            });
    }

}
