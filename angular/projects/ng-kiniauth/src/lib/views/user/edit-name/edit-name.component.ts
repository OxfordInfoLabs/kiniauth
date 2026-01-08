import {Component, EventEmitter, Input, OnDestroy, OnInit, Output, ViewEncapsulation} from '@angular/core';

@Component({
    selector: 'ka-edit-name',
    templateUrl: './edit-name.component.html',
    styleUrls: ['./edit-name.component.sass'],
    encapsulation: ViewEncapsulation.None,
    standalone: false
})
export class EditNameComponent implements OnInit, OnDestroy {

    @Input() authService;

    @Output('saved') saved: EventEmitter<any> = new EventEmitter();

    public newName = '';
    public currentPassword = '';
    public saveError: string;
    public user: any;

    constructor() {
    }

    ngOnInit() {
        return this.authService.getLoggedInUser().then(user => {
            this.user = user;
        });
    }

    ngOnDestroy(): void {

    }

    public saveNewName() {
        this.saveError = '';
        this.authService.changeUserName(this.newName, this.currentPassword)
            .then(user => {
                this.user = user;
                this.saved.emit(user);
            })
            .catch(err => {
                this.saveError = 'There was a problem updating your details, please check and try again.';
            });
    }

}
