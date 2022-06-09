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

    constructor(kcAuthService: AuthenticationService,
                private route: ActivatedRoute) {
        super(kcAuthService);
    }

    async ngOnInit() {
        super.ngOnInit();

        this.resetCode = this.route.snapshot.queryParams.code;

        this.authService.getEmailForPasswordReset(this.resetCode).then(resetEmail => {
            this.resetEmail = resetEmail;
        }).catch(err => {
            this.codeError = true;
        });
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
