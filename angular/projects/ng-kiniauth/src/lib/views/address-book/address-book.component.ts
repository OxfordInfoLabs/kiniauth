import { Component, EventEmitter, Input, ViewEncapsulation } from '@angular/core';
import { KinibindModel, KinibindRequestService } from 'ng-kinibind';
import { ContactService } from '../../services/contact.service';

@Component({
    selector: 'ka-address-book',
    templateUrl: './address-book.component.html',
    styleUrls: ['./address-book.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class AddressBookComponent {

    @Input() editContactURL: string;
    @Input() deleteContactURL: string;
    @Input() defaultContactURL: string;
    @Input() source: string;

    public contacts: KinibindModel = new KinibindModel();
    public reload: EventEmitter<boolean> = new EventEmitter<boolean>();
    public contactLoading;

    constructor(private contactService: ContactService,
                private kbRequest: KinibindRequestService) {
    }

    public deleteContact(contactId) {
        const message = 'Are you sure you would like to delete this contact?';
        if (window.confirm(message)) {
            return this.kbRequest.makeGetRequest(this.deleteContactURL, {
                params: {
                    contactId: contactId
                }
            }).toPromise().then(() => {
                this.reload.next(true);
            });
        }
    }

    public makeDefault(contactId) {
        return this.kbRequest.makeGetRequest(this.defaultContactURL, {
            params: {
                contactId: contactId
            }
        }).toPromise().then(() => {
            this.reload.next(true);
        });
    }

}
