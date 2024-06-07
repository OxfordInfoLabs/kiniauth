import {Component, EventEmitter, Input, OnInit, Output, ViewEncapsulation} from '@angular/core';

@Component({
    selector: 'ka-two-factor',
    templateUrl: './two-factor.component.html',
    styleUrls: ['./two-factor.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class TwoFactorComponent implements OnInit {

    @Input() authService;

    @Output('saved') saved: EventEmitter<any> = new EventEmitter();

    public user: any;
    public settings: any;
    public twoFACode: string;

    constructor() {
    }

    ngOnInit() {
        return this.authService.getLoggedInUser().then(user => {
            this.user = user;
            return user;
        }).then(() => {
            this.authService.generateTwoFactorSettings().then(settings => {
                this.settings = settings;
            });
        });
    }

    public verifyCode() {
        this.authService.authenticateNewTwoFactor(this.twoFACode, this.settings.secret)
            .then(res => {
                this.saved.emit(res);
            });
    }

}
