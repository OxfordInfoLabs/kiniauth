import {Component, EventEmitter, Input, OnInit, Output, ViewChild, ViewEncapsulation} from '@angular/core';
import { Router } from '@angular/router';
import { AuthenticationService } from '../../../services/authentication.service';
import { BaseComponent } from '../../base-component';
import {MatDialogRef} from '@angular/material/dialog';


@Component({
    selector: 'ka-login',
    templateUrl: './login.component.html',
    styleUrls: ['./login.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class LoginComponent extends BaseComponent implements OnInit {

    @ViewChild('captchaRef') captchaRef: any;

    @Input() loginRoute: string;
    @Input() recaptchaKey: string;
    @Input() preventRedirect = false;
    @Input() hideForgottenPassword = false;
    @Input() forgottenPasswordURL: string;
    @Input() facebookSSOURL: string;
    @Input() googleSSOURL: string;
    @Input() dialogRef: MatDialogRef<any>;

    @Output() loggedIn = new EventEmitter();

    public email: string;
    public forgottenEmail: string;
    public password: string;
    public loading = false;
    public loginError = false;
    public twoFA = false;
    public twoFACode: string;
    public twoFAError = false;
    public showRecaptcha = false;
    public recaptchaResponse: string;
    public activeSession = false;
    public forgottenPassword = false;
    public passwordResetSent = false;
    public trustBrowser = false;

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

    public startForgottenPassword() {
        if (this.forgottenPasswordURL) {
            window.location.href = this.forgottenPasswordURL;
        } else {
            this.forgottenPassword = true;
        }
    }

    public openSSO(link: string) {
        const popup = window.open(link, 'popup', 'popup=true,height=700,width=600');
        const timer = setInterval(async () => {
            if (popup.closed) {
                clearInterval(timer);
                const user = await this.authService.getLoggedInUser(true);
                if (user && !this.preventRedirect) {
                    return this.router.navigate(['/']);
                }
                if (this.preventRedirect && this.dialogRef) {
                    this.dialogRef.close();
                }
            }
        }, 250);
    }

    public login() {
        this.loginError = false;
        if (this.email && this.password) {
            this.loading = true;
            const clientTwoFactorData = localStorage.getItem('clientTwoFactorData');
            return this.authService.login(this.email, this.password, clientTwoFactorData || null, (this.showRecaptcha ? this.recaptchaResponse : null))
                .then(async (res: any) => {
                    this.loading = false;
                    if (res === 'REQUIRES_2FA') {
                        this.twoFA = true;
                        return true;
                    } else if (res === 'ACTIVE_SESSION') {
                        this.activeSession = true;
                    } else {
                        this.loggedIn.emit(res);
                        await this.authService.getLoggedInUser();
                        if (!this.preventRedirect) {
                            return this.router.navigate([this.loginRoute || '/']);
                        }
                    }
                })
                .catch(err => {
                    this.authService.getSessionData();
                    this.loginError = true;
                    if (this.captchaRef) {
                        this.captchaRef.reset();
                    }

                    this.loading = false;
                });
        }
    }

    public sendForgottenPassword() {
        this.authService.sendPasswordReset(this.forgottenEmail, this.recaptchaResponse).then(() => {
            this.passwordResetSent = true;
            setTimeout(() => {
                window.location.reload();
            }, 5000);
        });
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
                if (!this.preventRedirect) {
                    return this.router.navigate([this.loginRoute || '/']);
                }
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
                .then(async clientTwoFactorData => {
                    this.loading = false;
                    if (clientTwoFactorData && this.trustBrowser) {
                        localStorage.setItem('clientTwoFactorData', String(clientTwoFactorData));
                    }
                    this.loggedIn.emit(clientTwoFactorData);
                    await this.authService.getLoggedInUser();
                    if (!this.preventRedirect) {
                        return this.router.navigate([this.loginRoute || '/']);
                    }
                })
                .catch(error => {
                    this.authService.getSessionData();
                    this.twoFAError = true;
                    this.loading = false;
                    if (this.captchaRef) {
                        this.captchaRef.reset();
                    }
                    return error;
                });
        }
    }

}
