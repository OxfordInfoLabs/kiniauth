import { Injectable } from '@angular/core';
import { KinibindRequestService } from 'ng-kinibind';
import { KiniAuthModuleConfig } from '../../ng-kiniauth.module';
import { AuthenticationService } from './authentication.service';

@Injectable({
    providedIn: 'root'
})
export class AccountService {

    constructor(private kbRequest: KinibindRequestService,
                private config: KiniAuthModuleConfig,
                private authService: AuthenticationService) {
    }

    public getAccount() {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/account').toPromise()
            .catch(err => {
                return null;
            });
    }

    public changeAccountName(newName, password) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/account/changeName', {
            params: {
                newName,
                password: this.authService.getHashedPassword(password)
            }
        }).toPromise().then(res => {
            if (res) {
                return true;
            }
        });
    }

    public inviteUserToAccount(emailAddress, assignedRoles) {
        return this.kbRequest.makePostRequest(this.config.accessHttpURL + '/account/invite?emailAddress=' + emailAddress,
            assignedRoles).toPromise();
    }

}
