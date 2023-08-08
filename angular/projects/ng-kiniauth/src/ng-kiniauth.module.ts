import { ModuleWithProviders, NgModule } from '@angular/core';
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
import { MatLegacyPaginatorModule as MatPaginatorModule } from '@angular/material/legacy-paginator';
import { UserRolesComponent } from './lib/views/user-roles/user-roles.component';
import { MatIconModule } from '@angular/material/icon';
import { MatLegacyButtonModule as MatButtonModule } from '@angular/material/legacy-button';
import { EditRolesComponent } from './lib/views/user-roles/edit-roles/edit-roles.component';
import { MatLegacyMenuModule as MatMenuModule } from '@angular/material/legacy-menu';
import { InviteUserComponent } from './lib/views/invite-user/invite-user.component';
import { MatLegacyTabsModule as MatTabsModule } from '@angular/material/legacy-tabs';
import { EditDetailsComponent } from './lib/views/account-summary/edit-details/edit-details.component';
import { EditNameComponent } from './lib/views/account-summary/edit-name/edit-name.component';
import { EditAccountNameComponent } from './lib/views/account-summary/edit-account-name/edit-account-name.component';
import { RecaptchaModule } from 'ng-recaptcha';
import { NotificationsComponent } from './lib/views/notifications/notifications.component';
import {MatLegacyTableModule as MatTableModule} from '@angular/material/legacy-table';
import {MatLegacyChipsModule as MatChipsModule} from '@angular/material/legacy-chips';
import { NotificationComponent } from './lib/views/notifications/notification/notification.component';
import {MatLegacyCheckboxModule as MatCheckboxModule} from '@angular/material/legacy-checkbox';
import { AccountsComponent } from './lib/views/accounts/accounts.component';
import { InvitationComponent } from './lib/views/invitation/invitation.component';
import {HttpClientModule} from '@angular/common/http';
import { PasswordResetComponent } from './lib/views/password-reset/password-reset.component';
import { ChangePasswordComponent } from './lib/views/account-summary/change-password/change-password.component';
import { ApiKeysComponent } from './lib/views/api-keys/api-keys.component';

@NgModule({
    declarations: [
        AccountSummaryComponent,
        EditNameComponent,
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
        EditDetailsComponent,
        EditAccountNameComponent,
        NotificationsComponent,
        NotificationComponent,
        AccountsComponent,
        InvitationComponent,
        PasswordResetComponent,
        ChangePasswordComponent,
        ApiKeysComponent
    ],
    imports: [
        RouterModule,
        CommonModule,
        FormsModule,
        HttpClientModule,
        ReactiveFormsModule,
        MatPaginatorModule,
        MatIconModule,
        MatButtonModule,
        MatMenuModule,
        MatTabsModule,
        RecaptchaModule,
        MatTableModule,
        MatChipsModule,
        MatCheckboxModule
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
        EditAccountNameComponent,
        NotificationsComponent,
        NotificationComponent,
        AccountsComponent,
        InvitationComponent,
        PasswordResetComponent,
        ApiKeysComponent
    ]
})
export class NgKiniAuthModule {
    static forRoot(conf?: KiniAuthModuleConfig): ModuleWithProviders<NgKiniAuthModule> {
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
