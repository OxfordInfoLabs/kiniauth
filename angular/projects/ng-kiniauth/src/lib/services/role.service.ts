import { Injectable } from '@angular/core';
import { KiniAuthModuleConfig } from '../../ng-kiniauth.module';
import { HttpClient } from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class RoleService {

    constructor(private http: HttpClient,
                private config: KiniAuthModuleConfig) {
    }

    public getScopeAccesses() {
        return this.http.get(this.config.accessHttpURL + '/role/scopeAccesses')
            .toPromise();
    }
}
