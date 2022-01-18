import {Component, Input, OnInit} from '@angular/core';
import {AuthenticationService} from '../../services/authentication.service';
import {BaseComponent} from '../base-component';
import {ActivatedRoute} from '@angular/router';

@Component({
    selector: 'ka-invitation',
    templateUrl: './invitation.component.html',
    styleUrls: ['./invitation.component.sass']
})
export class InvitationComponent extends BaseComponent implements OnInit {

    @Input() authenticationService: any;

    public invitationCode: string;
    public details: any = {};
    public name = '';
    public password = '';
    public inviteAccepted = false;
    public invitationError = false;

    constructor(kcAuthService: AuthenticationService,
                private route: ActivatedRoute) {
        super(kcAuthService);
    }

    ngOnInit() {
        super.ngOnInit();

        this.invitationCode = this.route.snapshot.queryParams.invitationCode;

        this.authService.getInvitationDetails(this.invitationCode).then(details => {
            this.details = details;
        }).catch(err => {
            this.invitationError = true;
        });
    }

    public acceptInvitation() {
        this.authService.acceptInvitation(this.invitationCode, this.name, this.password, this.details.emailAddress).then(() => {
            this.inviteAccepted = true;
        });
    }

}
