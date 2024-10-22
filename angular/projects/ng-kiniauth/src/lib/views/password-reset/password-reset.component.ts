import {Component, Input, OnInit} from '@angular/core';
import {AuthenticationService} from '../../services/authentication.service';
import {ActivatedRoute} from '@angular/router';
import {BaseComponent} from '../base-component';

@Component({
    selector: 'ka-password-reset',
    templateUrl: './password-reset.component.html',
    styleUrls: ['./password-reset.component.sass']
})
export class PasswordResetComponent extends BaseComponent implements OnInit {

    @Input() authenticationService: any;
    @Input() loginRoute = '/login';
    @Input() recaptchaKey: string;

    public resetCode: string;
    public codeError = false;
    public resetError = false;
    public resetComplete = false;
    public resetEmail: string;
    public password: string;
    public confirmPassword: string;
    public recaptchaResponse: string;
    public isLengthOk = false;
    public isLowerCaseOk = false;
    public isUpperCaseOk = false;
    public isDigitOk = false;
    public isSpecialOk = false;
    public isPasswordOk = false;

    constructor(kcAuthService: AuthenticationService,
                private route: ActivatedRoute) {
        super(kcAuthService);
    }

    async ngOnInit() {
        super.ngOnInit();

        await this.authService.getSessionData();

        this.resetCode = this.route.snapshot.queryParams.code;

        this.authService.getEmailForPasswordReset(this.resetCode).then(resetEmail => {
            this.resetEmail = resetEmail;
        }).catch(err => {
            this.codeError = true;
        });
    }

    public passwordChange() {
        const strongPassword = new RegExp('(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])(?=.{8,})');
        this.isPasswordOk = strongPassword.test(this.password);

        const lower = new RegExp('(?=.*[a-z])');
        const upper = new RegExp('(?=.*[A-Z])');
        const digit = new RegExp('(?=.*[0-9])');
        const special = new RegExp('(?=.*[^A-Za-z0-9])');
        const length = new RegExp('(?=.{8,})');

        this.isLengthOk = length.test(this.password);
        this.isLowerCaseOk = lower.test(this.password);
        this.isUpperCaseOk = upper.test(this.password);
        this.isDigitOk = digit.test(this.password);
        this.isSpecialOk = special.test(this.password);
    }

    public recaptchaResolved(response) {
        this.recaptchaResponse = response;
    }

    public saveNewPassword() {
        this.authService.resetPassword(this.resetEmail, this.confirmPassword, this.resetCode, this.recaptchaResponse)
            .then(() => {
                this.resetComplete = true;
                setTimeout(() => {
                    window.location.href = this.loginRoute;
                }, 5000);
            })
            .catch(err => {
                this.resetError = true;
            });
    }

}
