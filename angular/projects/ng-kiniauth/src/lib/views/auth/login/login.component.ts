import { Component, Input, OnInit, ViewEncapsulation } from '@angular/core';
import { Router } from '@angular/router';
import { AuthenticationService } from '../../../services/authentication.service';
import { BaseComponent } from '../../base-component';

@Component({
    selector: 'ka-login',
    templateUrl: './login.component.html',
    styleUrls: ['./login.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class LoginComponent extends BaseComponent implements OnInit {

    @Input() loginRoute: string;
    @Input() recaptchaKey: string;

    public email: string;
    public password: string;
    public loading = false;
    public loginError = false;
    public twoFA = false;
    public twoFACode: string;
    public twoFAError = false;
    public showRecaptcha = false;
    public recaptchaResponse: string;
    public activeSession = false;

    constructor(private router: Router,
                kcAuthService: AuthenticationService) {
        super(kcAuthService);
    }

    ngOnInit() {
        super.ngOnInit();

        this.authService.sessionData.subscribe(session => {
            if (session && session.delayedCaptchas && session.delayedCaptchas['guest/auth/login']) {
                this.showRecaptcha = true;
            }
        });

        return Promise.resolve(true);
    }

    public recaptchaResolved(response) {
        this.recaptchaResponse = response;
    }

    public login() {
        this.loginError = false;
        if (this.email && this.password) {
            this.loading = true;
            return this.authService.login(this.email, this.password, (this.showRecaptcha ? this.recaptchaResponse : null))
                .then((res: any) => {
                    this.loading = false;
                    if (res === 'REQUIRES_2FA') {
                        this.twoFA = true;
                        return true;
                    } else if (res === 'ACTIVE_SESSION') {
                        this.activeSession = true;
                    } else {
                        return this.router.navigate([this.loginRoute || '/']);
                    }
                })
                .catch(err => {
                    this.authService.getSessionData();
                    this.loginError = true;
                    this.loading = false;
                });
        }
    }

    public closeActiveSession() {
        this.authService.closeActiveSession().then(res => {
            if (res === 'REQUIRES_2FA') {
                this.activeSession = false;
                this.twoFA = true;
                return true;
            } else if (res === 'ACTIVE_SESSION') {
                this.activeSession = true;
            } else {
                this.activeSession = false;
                return this.router.navigate([this.loginRoute || '/']);
            }
        });
    }

    public checkUsername() {
        this.authService.doesUserExist(this.email).then(res => {
        });
    }

    public authenticate() {
        this.loading = true;
        if (this.twoFACode) {
            return this.authService.authenticateTwoFactor(this.twoFACode)
                .then(user => {
                    this.loading = false;
                    this.router.navigate([this.loginRoute || '/']);
                    return user;
                })
                .catch(error => {
                    this.authService.getSessionData();
                    this.twoFAError = true;
                    this.loading = false;
                    return error;
                });
        }
    }

}
