import { Component, Input, OnInit } from '@angular/core';
import { AuthenticationService } from '../services/authentication.service';

@Component({
    selector: 'ka-base-component',
    template: ''
})
export class BaseComponent implements OnInit {

    @Input() authenticationService;

    public authService;

    constructor(public kcAuthService: AuthenticationService) {

    }

    ngOnInit(): void {
        this.authService = this.authenticationService ? this.authenticationService : this.kcAuthService;
    }

}
