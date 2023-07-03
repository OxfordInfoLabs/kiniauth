import { Injectable } from '@angular/core';
import { KiniAuthModuleConfig } from '../../ng-kiniauth.module';
import {HttpClient} from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class ContactService {

    constructor(private http: HttpClient,
                private config: KiniAuthModuleConfig) {
    }

    public getContact(contactId) {
        return this.http.get(this.config.accessHttpURL + '/contact', {
            params: {
                contactId
            }
        }).toPromise();
    }

    public getContacts() {
        return this.http.get(this.config.accessHttpURL + '/contact/contacts').toPromise();
    }

    public setDefaultContact(contactId) {
        return this.http.get(this.config.accessHttpURL + '/contact/default', {
            params: {contactId}
        }).toPromise();
    }

    public deleteContact(contactId) {
        return this.http.get(this.config.accessHttpURL + '/contact/delete', {
            params: {contactId}
        }).toPromise();
    }
}
