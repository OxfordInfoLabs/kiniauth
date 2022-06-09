import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {BaseComponent} from '../../base-component';
import {AuthenticationService} from '../../../services/authentication.service';

@Component({
    selector: 'ka-change-password',
    templateUrl: './change-password.component.html',
    styleUrls: ['./change-password.component.sass']
})
export class ChangePasswordComponent extends BaseComponent implements OnInit {

    @Input() email: string;

    @Output('saved') saved: EventEmitter<any> = new EventEmitter();

    public password: string;
    public confirmPassword: string;
    public existingPassword: string;
    public saveError = false;
    public changeComplete = false;

    constructor(kcAuthService: AuthenticationService) {
        super(kcAuthService);
    }

    ngOnInit() {
        super.ngOnInit();
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
