import { Component, EventEmitter, OnInit, Output, ViewEncapsulation } from '@angular/core';
import { AuthenticationService } from '../../../services/authentication.service';
import { BaseComponent } from '../../base-component';

@Component({
    selector: 'ka-edit-backup-email',
    templateUrl: './edit-backup-email.component.html',
    styleUrls: ['./edit-backup-email.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class EditBackupEmailComponent extends BaseComponent implements OnInit {

    @Output('saved') saved: EventEmitter<any> = new EventEmitter();

    public newEmailAddress = '';
    public currentPassword = '';
    public saveError: string;
    public emailAvailable = true;

    public user: any;

    constructor(public kcAuthService: AuthenticationService) {
        super(kcAuthService);
    }

    ngOnInit() {
        super.ngOnInit();
        return this.authService.getLoggedInUser().then(user => {
            this.user = user;
        });
    }

    public checkEmail() {
        this.authService.emailAvailable(this.newEmailAddress).then(res => {
            this.emailAvailable = res;
        });
    }

    public saveEmailAddress() {
        this.saveError = '';
        this.authService.changeUserBackEmailAddress(this.newEmailAddress, this.currentPassword)
            .then(user => {
                this.user = user;
                this.saved.emit(user);
            })
            .catch(err => {
                if (err.error.validationErrors.backupEmailAddress.email.errorMessage) {
                    this.saveError = 'Email error: ' + err.error.validationErrors.backupEmailAddress.email.errorMessage;
                } else {
                    this.saveError = 'There was a problem changing the email address, please check and try again.'
                }
            });
    }

}
