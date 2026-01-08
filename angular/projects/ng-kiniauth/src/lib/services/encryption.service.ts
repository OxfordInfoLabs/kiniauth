import {Injectable} from '@angular/core';
import {KiniAuthModuleConfig} from '../../ng-kiniauth.module';
import {HttpClient} from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class EncryptionService {

    constructor(private config: KiniAuthModuleConfig,
                private http: HttpClient) {
    }

    public encryptSSOText(authenticatorKey: string, text: string) {
        return this.http.post(this.config.accessHttpURL + '/encrypt/sso', {
            authenticatorKey, text
        }).toPromise();
    }
}
