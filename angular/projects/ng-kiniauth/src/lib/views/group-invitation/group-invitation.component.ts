import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from '@angular/router';
import {AuthenticationService} from '../../services/authentication.service';
import {GroupService} from '../../services/group.service';

@Component({
    selector: 'ka-group-invitation',
    templateUrl: './group-invitation.component.html',
    styleUrls: ['./group-invitation.component.sass'],
    standalone: false
})
export class GroupInvitationComponent implements OnInit {

    public inviteAccepted = false;
    public inviteCancelled = false;
    public invitationError = false;
    public invitationCode: string;
    public details: any = {};

    constructor(private route: ActivatedRoute,
                private authService: AuthenticationService,
                private groupService: GroupService) {

    }

    async ngOnInit() {
        await this.authService.getSessionData();

        this.invitationCode = this.route.snapshot.queryParams.invitationCode;

        try {
            this.details = await this.groupService.getInvitationDetails(this.invitationCode);
            console.log(this.details);
        } catch (e) {
            this.invitationError = true;
        }
    }


    public async acceptInvitation() {
        try {
            await this.groupService.acceptInvitation(this.invitationCode);
            this.inviteAccepted = true;
        } catch (e){
            this.invitationError = true;
        }
    }


}
