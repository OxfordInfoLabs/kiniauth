import { Component, Input, OnDestroy, OnInit, ViewEncapsulation } from '@angular/core';
import { Subscription } from 'rxjs/internal/Subscription';
import { Subject } from 'rxjs/internal/Subject';
import { AuthenticationService } from '../../services/authentication.service';
import { BaseComponent } from '../base-component';

@Component({
    selector: 'ka-account-summary',
    templateUrl: './account-summary.component.html',
    styleUrls: ['./account-summary.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class AccountSummaryComponent extends BaseComponent implements OnInit, OnDestroy {

    @Input() authenticationService: any;
    @Input() showPasswordReset = true;

    public security: any;
    public twoFactorConfig: any;
    public reloadTwoFactor: Subject<boolean> = new Subject();
    public isLoading = true;

    public editEmail = false;
    public editMobile = false;
    public editBackup = false;
    public enableTwoFa = false;

    private userSub: Subscription;

    constructor(kcAuthService: AuthenticationService) {
        super(kcAuthService);
    }

    ngOnInit() {
        super.ngOnInit();
        this.userSub = this.authService.authUser.subscribe(user => this.security = user);
        return this.authService.getLoggedInUser();
    }

    ngOnDestroy(): void {
        this.userSub.unsubscribe();
    }

    public resetAccountPassword() {
        const message = 'We are going to reset the account password to a temporary one and email the registered ' +
            'email address. Are you sure you wish to proceed?';
        if (window.confirm(message)) {
            this.authService.resetAccountPassword();
        }
    }

    public disable2FA() {
        const message = 'Are you sure you would like to turn off Two Factor Authentication?';
        if (window.confirm(message)) {
            this.authService.disableTwoFactor().then(() => {
                this.reloadTwoFactor.next(true);
            });
        }
    }


}
