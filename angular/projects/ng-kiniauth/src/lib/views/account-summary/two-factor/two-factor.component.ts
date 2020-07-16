import { Component, EventEmitter, OnInit, Output, ViewEncapsulation } from '@angular/core';
import { BaseComponent } from '../../base-component';
import { AuthenticationService } from '../../../services/authentication.service';
import { Router } from '@angular/router';

@Component({
    selector: 'ka-two-factor',
    templateUrl: './two-factor.component.html',
    styleUrls: ['./two-factor.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class TwoFactorComponent extends BaseComponent implements OnInit {

    @Output('saved') saved: EventEmitter<any> = new EventEmitter();

    public user: any;
    public settings: any;
    public twoFACode: string;

    constructor(public kcAuthService: AuthenticationService,
                private router: Router) {
        super(kcAuthService);
    }

    ngOnInit() {
        super.ngOnInit();

        return this.authService.getLoggedInUser().then(user => {
            this.user = user;
            return
        }).then(() => {
            this.authService.generateTwoFactorSettings().then(settings => {
                this.settings = settings;
            });
        });
    }

    public verifyCode() {
        this.authService.authenticateNewTwoFactor(this.twoFACode, this.settings.secret)
            .then(res => {
                this.saved.emit(res);
            });
    }

}
