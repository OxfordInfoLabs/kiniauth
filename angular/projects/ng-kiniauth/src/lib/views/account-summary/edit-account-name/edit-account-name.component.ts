import { Component, EventEmitter, OnDestroy, OnInit, Output, ViewEncapsulation } from '@angular/core';
import { BaseComponent } from '../../base-component';
import { AuthenticationService } from '../../../services/authentication.service';
import { AccountService } from '../../../services/account.service';

@Component({
    selector: 'ka-edit-account-name',
    templateUrl: './edit-account-name.component.html',
    styleUrls: ['./edit-account-name.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class EditAccountNameComponent extends BaseComponent implements OnInit, OnDestroy {

    @Output('saved') saved: EventEmitter<any> = new EventEmitter();

    public newName = '';
    public currentPassword = '';
    public saveError: string;
    public user: any;

    constructor(kcAuthService: AuthenticationService,
                private accountService: AccountService) {
        super(kcAuthService);
    }

    ngOnInit() {
        super.ngOnInit();
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
