import {Component, EventEmitter, Input, OnDestroy, OnInit, Output, ViewEncapsulation} from '@angular/core';

@Component({
    selector: 'ka-edit-account-name',
    templateUrl: './edit-account-name.component.html',
    styleUrls: ['./edit-account-name.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class EditAccountNameComponent implements OnInit, OnDestroy {

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
            .then(user => {
                this.user = user;
                this.saved.emit(user);
            })
            .catch(err => {
                this.saveError = 'There was a problem changing the account name, please check and try again.';
            });
    }

}
