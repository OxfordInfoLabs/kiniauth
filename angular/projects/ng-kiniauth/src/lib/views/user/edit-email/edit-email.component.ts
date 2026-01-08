import {Component, EventEmitter, Input, OnDestroy, OnInit, Output, ViewEncapsulation} from '@angular/core';

@Component({
    selector: 'ka-edit-email',
    templateUrl: './edit-email.component.html',
    styleUrls: ['./edit-email.component.sass'],
    encapsulation: ViewEncapsulation.None,
    standalone: false
})
export class EditEmailComponent implements OnInit, OnDestroy {

    @Input() authService;

    @Output('saved') saved: EventEmitter<any> = new EventEmitter();

    public newEmailAddress = '';
    public currentPassword = '';
    public saveError: string;
    public emailAvailable = true;
    public user: any;

    constructor() {
    }

    ngOnInit() {
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
                    this.saveError = 'There was a problem changing the email address, please check and try again.';
                }
            });
    }

}
