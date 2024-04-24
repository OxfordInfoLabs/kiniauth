import {Component, OnInit} from '@angular/core';
import {AccountService} from '../../services/account.service';

@Component({
    selector: 'ka-account-discoverability',
    templateUrl: './account-discoverability.component.html',
    styleUrls: ['./account-discoverability.component.sass']
})
export class AccountDiscoverabilityComponent implements OnInit {

    public discoverabilitySettings: any;

    constructor(private accountService: AccountService) {
    }

    async ngOnInit() {
        this.discoverabilitySettings = await this.accountService.getAccountDiscoverabilitySettings();
    }

    // Update discoverability settings
    async updateDiscoverability(checked: boolean) {
        await this.accountService.setAccountDiscoverable(checked);
    }

    // Generate external identifier
    async generateExternalIdentifier() {
        this.discoverabilitySettings.externalIdentifier = await this.accountService.generateAccountExternalIdentifier();
    }

    // Unset external identifier
    async unsetExternalIdentifier() {
        await this.accountService.unsetAccountExternalIdentifier();
        this.discoverabilitySettings.externalIdentifier = null;
    }
}
