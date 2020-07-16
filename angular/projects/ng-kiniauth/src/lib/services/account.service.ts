import { Injectable } from '@angular/core';
import { KinibindRequestService } from 'ng-kinibind';
import { KiniAuthModuleConfig } from '../../ng-kiniauth.module';

@Injectable({
    providedIn: 'root'
})
export class AccountService {

    constructor(private kbRequest: KinibindRequestService,
                private config: KiniAuthModuleConfig) {
    }

    public inviteUserToAccount(emailAddress, assignedRoles) {
        return this.kbRequest.makePostRequest(this.config.accessHttpURL + '/account/invite?emailAddress=' + emailAddress,
            assignedRoles).toPromise();
    }
}
