import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'ka-change-password',
    templateUrl: './change-password.component.html',
    styleUrls: ['./change-password.component.sass']
})
export class ChangePasswordComponent implements OnInit {

    @Input() email: string;
    @Input() authService: any;

    @Output('saved') saved: EventEmitter<any> = new EventEmitter();

    public password: string;
    public confirmPassword: string;
    public existingPassword: string;
    public saveError = false;
    public changeComplete = false;
    public isLengthOk = false;
    public isLowerCaseOk = false;
    public isUpperCaseOk = false;
    public isDigitOk = false;
    public isSpecialOk = false;
    public isPasswordOk = false;

    constructor() {
    }

    ngOnInit() {
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

    public saveNewPassword() {
        this.saveError = false;
        this.authService.changeUserPassword(this.confirmPassword, this.existingPassword, this.email)
            .then(() => {
                this.changeComplete = true;
                setTimeout(() => {
                    this.saved.emit(Date.now());
                }, 3000);
            })
            .catch(err => {
                this.saveError = true;
                setTimeout(() => {
                    this.saved.emit(Date.now());
                }, 3000);
            });
    }

}
