import { Component, EventEmitter, OnDestroy, OnInit, Output, ViewEncapsulation } from '@angular/core';
import { BaseComponent } from '../../base-component';
import { AuthenticationService } from '../../../services/authentication.service';
import { Subscription } from 'rxjs/internal/Subscription';

@Component({
    selector: 'ka-edit-email',
    templateUrl: './edit-email.component.html',
    styleUrls: ['./edit-email.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class EditEmailComponent extends BaseComponent implements OnInit, OnDestroy {

    @Output('saved') saved: EventEmitter<any> = new EventEmitter();

    public newEmailAddress = '';
    public currentPassword = '';
    public saveError: string;
    public emailAvailable = true;
    public user: any;

    private userSub: Subscription;

    constructor(kcAuthService: AuthenticationService) {
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

    public checkEmail() {
        this.authService.emailAvailable(this.newEmailAddress).then(res => {
            this.emailAvailable = res;
        });
    }

    public saveEmailAddress() {
        this.saveError = '';
        this.authService.changeUserEmailAddress(this.newEmailAddress, this.currentPassword)
            .then(user => {
                this.user = user;
                this.saved.emit(user);
            })
            .catch(err => {
                if (err.error.validationErrors.emailAddress.email.errorMessage) {
                    this.saveError = 'Email error: ' + err.error.validationErrors.emailAddress.email.errorMessage;
                } else {
                    this.saveError = 'There was a problem changing the email address, please check and try again.'
                }
            });
    }

}
