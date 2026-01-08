import {Component, Input, OnInit} from '@angular/core';
import {AccountService} from '../../../services/account.service';
import * as lodash from 'lodash';
import {EncryptionService} from '../../../services/encryption.service';

const _ = lodash.default;

@Component({
    selector: 'ka-sso-configuration',
    templateUrl: './sso-configuration.component.html',
    styleUrls: ['./sso-configuration.component.sass'],
    standalone: false
})
export class SsoConfigurationComponent implements OnInit {

    @Input() ssoLoginURL: string;

    public accountSettings: any;
    public account: any;
    public clientSecret: string;

    constructor(private accountService: AccountService,
                private encryptionService: EncryptionService) {
    }

    async ngOnInit() {
        this.accountSettings = await this.accountService.getAccountSettings();
        this.account = await this.accountService.getAccount();

        // Initialise the OpenID settings if none exist
        if (!this.accountSettings.oidc) {
            this.accountSettings.oidc = {account: this.account.name, provider: _.camelCase(this.account.name)};
        }

        // Initialise the SAML settings if none exist
        if (!this.accountSettings.saml) {
            this.accountSettings.saml = {account: this.account.name, provider: _.camelCase(this.account.name)};
        }
    }

    public updateClientSecret(settings: any) {
        delete settings.clientSecret;
        delete settings.last4ClientSecret;
    }

    public async save(authenticatorKey: string) {
        if (this.clientSecret) {
            this.accountSettings[authenticatorKey].clientSecret = await this.encryptionService.encryptSSOText(
                authenticatorKey,
                this.clientSecret
            );
            this.accountSettings[authenticatorKey].last4ClientSecret = this.clientSecret.slice(-4);
            this.clientSecret = '';
        }

        await this.accountService.updateAccountSettings(this.accountSettings);
    }

}
