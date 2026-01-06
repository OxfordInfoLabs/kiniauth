import {Injectable} from '@angular/core';
import { HttpClient } from '@angular/common/http';
import {KiniAuthModuleConfig} from '../../ng-kiniauth.module';
import * as lodash from 'lodash';

const _ = lodash.default;

@Injectable({
    providedIn: 'root'
})
export class CommunicationService {

    constructor(private http: HttpClient,
                private config: KiniAuthModuleConfig) {
    }

    public filterStoredEmails(recipientAddress = null, search = null, limit = 25, offset = 0)  {
        return this.http.post(this.config.accessHttpURL + '/communication/email/filter?limit=' + limit + '&offset=' + offset,
            _.omitBy({recipientAddress, search}, _.isNil)
        );
    }

    public getStoredEmailContent(id: number) {
        return this.http.get(this.config.accessHttpURL + '/communication/email/' + id).toPromise();
    }
}
