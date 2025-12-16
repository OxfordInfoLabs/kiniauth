import {Component, Input, OnInit} from '@angular/core';
import {AccountService} from '../../../services/account.service';
import * as lodash from 'lodash';

const _ = lodash.default;

@Component({
    selector: 'ka-sso-configuration',
    templateUrl: './sso-configuration.component.html',
    styleUrls: ['./sso-configuration.component.sass']
})
export class SsoConfigurationComponent implements OnInit {

    @Input() ssoLoginURL: string;

    public accountSettings: any;
    public account: any;

    constructor(private accountService: AccountService) {
    }

    async ngOnInit() {
        this.accountSettings = await this.accountService.getAccountSettings();
        this.account = await this.accountService.getAccount();

        // Initialise the OpenID settings if none exist
        if (!this.accountSettings.openId) {
            this.accountSettings.openId = {account: this.account.name, provider: _.camelCase(this.account.name)};
        }

        // Initialise the SAML settings if none exist
        if (!this.accountSettings.saml) {
            this.accountSettings.saml = {account: this.account.name, provider: _.camelCase(this.account.name)};
        }
    }

    public async save() {
        await this.accountService.updateAccountSettings(this.accountSettings);
    }

}
