import { ModuleWithProviders, NgModule } from '@angular/core';
import { RouterModule } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AccountSummaryComponent } from './lib/views/user/account-summary.component';
import { EditEmailComponent } from './lib/views/user/edit-email/edit-email.component';
import { TwoFactorComponent } from './lib/views/user/two-factor/two-factor.component';
import { LoginComponent } from './lib/views/auth/login/login.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { InlineModalComponent } from './lib/views/inline-modal/inline-modal.component';
import { BaseComponent } from './lib/views/base-component';
import { EditBackupEmailComponent } from './lib/views/user/edit-backup-email/edit-backup-email.component';
import { EditMobileComponent } from './lib/views/user/edit-mobile/edit-mobile.component';
import { AddressBookComponent } from './lib/views/address-book/address-book.component';
import { ContactDetailsComponent } from './lib/views/contact-details/contact-details.component';
import { CountryCodesDirective } from './lib/directives/country-codes/country-codes.directive';
import { PostcodeLookupDirective } from './lib/directives/postcode-lookup/postcode-lookup.directive';
import { AccountUsersComponent } from './lib/views/account/account-users/account-users.component';
import { MatPaginatorModule } from '@angular/material/paginator';
import { UserRolesComponent } from './lib/views/user-roles/user-roles.component';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { EditRolesComponent } from './lib/views/user-roles/edit-roles/edit-roles.component';
import { MatMenuModule } from '@angular/material/menu';
import { InviteUserComponent } from './lib/views/invite-user/invite-user.component';
import { MatTabsModule } from '@angular/material/tabs';
import { EditDetailsComponent } from './lib/views/user/edit-details/edit-details.component';
import { EditNameComponent } from './lib/views/user/edit-name/edit-name.component';
import { AccountCoreDetailsComponent } from './lib/views/account/account-core-details/account-core-details.component';
import { RecaptchaModule } from 'ng-recaptcha';
import { NotificationsComponent } from './lib/views/notifications/notifications.component';
import {MatTableModule} from '@angular/material/table';
import {MatChipsModule} from '@angular/material/chips';
import { NotificationComponent } from './lib/views/notifications/notification/notification.component';
import {MatCheckboxModule} from '@angular/material/checkbox';
import { AccountsComponent } from './lib/views/accounts/accounts.component';
import { InvitationComponent } from './lib/views/invitation/invitation.component';
import { provideHttpClient, withInterceptorsFromDi } from '@angular/common/http';
import { PasswordResetComponent } from './lib/views/password-reset/password-reset.component';
import { ChangePasswordComponent } from './lib/views/user/change-password/change-password.component';
import { SecurityComponent } from './lib/views/security/security.component';
import { AccountDiscoverabilityComponent } from './lib/views/account/account-discoverability/account-discoverability.component';
import { ExportProjectComponent } from './lib/views/export-project/export-project.component';
import { ImportProjectComponent } from './lib/views/import-project/import-project.component';
import {MatSnackBarModule} from '@angular/material/snack-bar';
import { GroupInvitationComponent } from './lib/views/group-invitation/group-invitation.component';

@NgModule({ declarations: [
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
        AccountCoreDetailsComponent,
        NotificationsComponent,
        NotificationComponent,
        AccountsComponent,
        InvitationComponent,
        PasswordResetComponent,
        ChangePasswordComponent,
        SecurityComponent,
        AccountDiscoverabilityComponent,
        ExportProjectComponent,
        ImportProjectComponent,
        GroupInvitationComponent
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
        AccountCoreDetailsComponent,
        NotificationsComponent,
        NotificationComponent,
        AccountsComponent,
        InvitationComponent,
        PasswordResetComponent,
        SecurityComponent,
        AccountDiscoverabilityComponent,
        ExportProjectComponent,
        ImportProjectComponent,
        GroupInvitationComponent
    ], imports: [RouterModule,
        CommonModule,
        FormsModule,
        ReactiveFormsModule,
        MatPaginatorModule,
        MatIconModule,
        MatButtonModule,
        MatMenuModule,
        MatTabsModule,
        RecaptchaModule,
        MatTableModule,
        MatChipsModule,
        MatCheckboxModule,
        MatSnackBarModule], providers: [provideHttpClient(withInterceptorsFromDi())] })
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
