import { Injectable } from '@angular/core';
import { KinibindRequestService } from 'ng-kinibind';

@Injectable({
    providedIn: 'root'
})
export class ContactService {

    constructor(private kbRequest: KinibindRequestService) {
    }

    public setDefaultContact(contactId) {
        return this.kbRequest.makeGetRequest('/internal/contacts/defaultContact', {
            params: {
                contactId: contactId
            }
        }).toPromise();
    }

    public deleteContact(contactId) {
        return this.kbRequest.makeGetRequest('/contact/delete', {
            params: {
                contactId: contactId
            }
        }).toPromise();
    }
}
