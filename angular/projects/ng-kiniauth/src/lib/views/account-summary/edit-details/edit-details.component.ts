import { Component, EventEmitter, Input, OnDestroy, OnInit, Output, ViewEncapsulation } from '@angular/core';
import { Subscription } from 'rxjs/internal/Subscription';
import { AuthenticationService } from '../../../services/authentication.service';
import { BaseComponent } from '../../base-component';

@Component({
    selector: 'ka-edit-details',
    templateUrl: './edit-details.component.html',
    styleUrls: ['./edit-details.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class EditDetailsComponent extends BaseComponent implements OnInit, OnDestroy {

    @Input() user: any;
    @Output('saved') saved: EventEmitter<any> = new EventEmitter();

    public newEmailAddress = '';
    public newName = '';
    public currentPassword = '';
    public saveError: string;
    public emailAvailable = true;

    private userSub: Subscription;

    constructor(kcAuthService: AuthenticationService) {
        super(kcAuthService);
    }

    ngOnInit() {
        super.ngOnInit();
        if (!this.user) {
            return this.authService.getLoggedInUser().then(user => {
                this.user = user;
                this.newName = user.name;
                this.newEmailAddress = user.emailAddress;
            });
        }
        this.newName = this.user.name;
        this.newEmailAddress = this.user.emailAddress;
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
        this.authService.changeUserDetails(this.newEmailAddress, this.newName, this.currentPassword, this.user.id)
            .then(user => {
                this.user = user;
                this.saved.emit(user);
            })
            .catch(err => {
                if (err.error.validationErrors.emailAddress.email.errorMessage) {
                    this.saveError = 'Email error: ' + err.error.validationErrors.emailAddress.email.errorMessage;
                } else {
                    this.saveError = 'There was a problem updating your details, please check and try again.'
                }
            });
    }

}
