import { Injectable } from '@angular/core';
import { KinibindRequestService } from 'ng-kinibind';
import { KiniAuthModuleConfig } from '../../ng-kiniauth.module';

@Injectable({
    providedIn: 'root'
})
export class ContactService {

    constructor(private kbRequest: KinibindRequestService,
                private config: KiniAuthModuleConfig) {
    }

    public getContact(contactId) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/contact', {
            params: {
                contactId
            }
        }).toPromise();
    }

    public getContacts() {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/contact/contacts').toPromise();
    }

    public setDefaultContact(contactId) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/contact/default', {
            params: {
                contactId: contactId
            }
        }).toPromise();
    }

    public deleteContact(contactId) {
        return this.kbRequest.makeGetRequest(this.config.accessHttpURL + '/contact/delete', {
            params: {
                contactId: contactId
            }
        }).toPromise();
    }
}
