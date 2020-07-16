import { ModuleWithProviders, NgModule } from '@angular/core';
import { NgKinibindModule } from 'ng-kinibind';
import { RouterModule } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AccountSummaryComponent } from './lib/views/account-summary/account-summary.component';
import { EditEmailComponent } from './lib/views/account-summary/edit-email/edit-email.component';
import { TwoFactorComponent } from './lib/views/account-summary/two-factor/two-factor.component';
import { LoginComponent } from './lib/views/auth/login/login.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { InlineModalComponent } from './lib/views/inline-modal/inline-modal.component';
import { BaseComponent } from './lib/views/base-component';
import { EditBackupEmailComponent } from './lib/views/account-summary/edit-backup-email/edit-backup-email.component';
import { EditMobileComponent } from './lib/views/account-summary/edit-mobile/edit-mobile.component';
import { AddressBookComponent } from './lib/views/address-book/address-book.component';
import { ContactDetailsComponent } from './lib/views/contact-details/contact-details.component';
import { CountryCodesDirective } from './lib/directives/country-codes/country-codes.directive';
import { PostcodeLookupDirective } from './lib/directives/postcode-lookup/postcode-lookup.directive';
import { AccountUsersComponent } from './lib/views/account-users/account-users.component';
import { MatPaginatorModule } from '@angular/material/paginator';
import { UserRolesComponent } from './lib/views/user-roles/user-roles.component';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { EditRolesComponent } from './lib/views/user-roles/edit-roles/edit-roles.component';
import { MatMenuModule } from '@angular/material/menu';
import { InviteUserComponent } from './lib/views/invite-user/invite-user.component';
import { MatTabsModule } from '@angular/material/tabs';
import { EditDetailsComponent } from './lib/views/account-summary/edit-details/edit-details.component';

@NgModule({
    declarations: [
        AccountSummaryComponent,
        EditEmailComponent,
        TwoFactorComponent,
        LoginComponent,
        InlineModalComponent,
        BaseComponent,
        EditBackupEmailComponent,
        EditMobileComponent,
        AddressBookComponent,
        ContactDetailsComponent,
        CountryCodesDirective,
        PostcodeLookupDirective,
        AccountUsersComponent,
        UserRolesComponent,
        EditRolesComponent,
        InviteUserComponent,
        EditDetailsComponent
    ],
    imports: [
        NgKinibindModule,
        RouterModule,
        CommonModule,
        FormsModule,
        ReactiveFormsModule,
        MatPaginatorModule,
        MatIconModule,
        MatButtonModule,
        MatMenuModule,
        MatTabsModule
    ],
    exports: [
        AccountSummaryComponent,
        EditEmailComponent,
        TwoFactorComponent,
        LoginComponent,
        AddressBookComponent,
        ContactDetailsComponent,
        AccountUsersComponent,
        UserRolesComponent,
        EditRolesComponent,
        InviteUserComponent,
    ]
})
export class NgKiniAuthModule {
    static forRoot(conf?: KiniAuthModuleConfig): ModuleWithProviders {
        return {
            ngModule: NgKiniAuthModule,
            providers: [
                { provide: KiniAuthModuleConfig, useValue: conf || {} }
            ]
        };
    }
}

export class KiniAuthModuleConfig {
    guestHttpURL: string;
    accessHttpURL: string;
}
