import {Component, EventEmitter, Input, OnInit, Output, ViewEncapsulation} from '@angular/core';

@Component({
    selector: 'ka-edit-mobile',
    templateUrl: './edit-mobile.component.html',
    styleUrls: ['./edit-mobile.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class EditMobileComponent implements OnInit {

    @Input() authService;

    @Output('saved') saved: EventEmitter<any> = new EventEmitter();

    public newMobile = '';
    public currentPassword = '';
    public saveError: string;

    public user: any;

    constructor() {
    }

    ngOnInit() {
        return this.authService.getLoggedInUser().then(user => {
            this.user = user;
        });
    }

    public saveMobileNumber() {
        this.saveError = '';
        this.authService.changeUserMobile(this.newMobile, this.currentPassword)
            .then(user => {
                this.user = user;
                this.saved.emit(user);
            })
            .catch(err => {
                if (err.error.validationErrors.mobileNumber.regexp.errorMessage) {
                    this.saveError = 'Number error: ' + err.error.validationErrors.mobileNumber.regexp.errorMessage;
                } else {
                    this.saveError = 'There was a problem changing the mobile number, please check and try again.';
                }
            });
    }

}
