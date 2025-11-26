import {Component, Input, OnInit} from '@angular/core';
import {AuthenticationService} from '../../../services/authentication.service';

@Component({
    selector: 'ka-sso-initialisation',
    templateUrl: './sso-initialisation.component.html',
    styleUrls: ['./sso-initialisation.component.sass']
})
export class SsoInitialisationComponent implements OnInit {

    @Input() provider: string;

    constructor(private authService: AuthenticationService) {
    }

    async ngOnInit() {
        try {
            const url: string = await this.authService.getSSOUri(this.provider);
            window.location.href = url;
        } catch (e) {
            console.error('SSO ERROR', e);
        }
    }

}
