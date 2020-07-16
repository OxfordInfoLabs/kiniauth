import { Injectable } from '@angular/core';
import { KinibindRequestService } from 'ng-kinibind';
import { KiniAuthModuleConfig } from '../../ng-kiniauth.module';

@Injectable({
    providedIn: 'root'
})
export class RoleService {

    constructor(private kbRequest: KinibindRequestService,
                private config: KiniAuthModuleConfig) {
    }

    public getScopeAccesses() {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/role/scopeAccesses')
            .toPromise();
    }
}
