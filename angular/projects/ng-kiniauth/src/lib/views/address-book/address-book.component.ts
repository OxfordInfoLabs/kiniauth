import { Component, EventEmitter, Input, OnInit, ViewEncapsulation } from '@angular/core';
import { ContactService } from '../../services/contact.service';

@Component({
    selector: 'ka-address-book',
    templateUrl: './address-book.component.html',
    styleUrls: ['./address-book.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class AddressBookComponent implements OnInit {

    @Input() editContactURL: string;
    @Input() deleteContactURL: string;
    @Input() defaultContactURL: string;
    @Input() source: string;

    public contacts: any = {};
    public reload: EventEmitter<boolean> = new EventEmitter<boolean>();
    public contactLoading;

    constructor(private contactService: ContactService) {
    }

    ngOnInit() {
        this.contactService.getContacts().then(contacts => {
            this.contacts = contacts;
        });
    }

    public deleteContact(contactId) {
        const message = 'Are you sure you would like to delete this contact?';
        if (window.confirm(message)) {
            return this.contactService.deleteContact(contactId).then(() => {
                this.reload.next(true);
            });
        }
    }

    public makeDefault(contactId) {
        return this.contactService.setDefaultContact(contactId).then(() => {
            this.reload.next(true);
        });
    }

}
