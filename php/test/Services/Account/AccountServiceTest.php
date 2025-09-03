<?php


namespace Kiniauth\Test\Services\Account;

use Kiniauth\Exception\Security\InvalidUserEmailDomainException;
use Kiniauth\Exception\Security\UserAlreadyAttachedToAccountException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Application\Activity;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Objects\Workflow\PendingAction;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\RoleService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Account\AccountDiscoveryItem;
use Kiniauth\ValueObjects\Account\AccountInvitation;
use Kiniauth\ValueObjects\Security\AssignedRole;
use Kiniauth\ValueObjects\Security\ScopeObjectRolesAssignment;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;


include_once __DIR__ . "/../../autoloader.php";

class AccountServiceTest extends TestBase {

    /**
     * @var AccountService
     */
    private $mockedAccountService;


    /**
     * @var AccountService
     */
    private $accountService;


    /**
     * @var PendingActionService
     */
    private $pendingActionService;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;


    /**
     * @var MockObject
     */
    private $mockPendingActionService;


    /**
     * @var MockObject
     */
    private $mockEmailService;


    /**
     * @var MockObject
     */
    private $userService;

    /**
     * Constructor
     *
     * AccountServiceTest constructor.
     */
    public function setUp(): void {
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);

        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);
        $this->mockPendingActionService = $mockObjectProvider->getMockInstance(PendingActionService::class);
        $this->mockEmailService = $mockObjectProvider->getMockInstance(EmailService::class);


        $this->mockedAccountService = new AccountService(Container::instance()->get(SecurityService::class), $this->mockPendingActionService, $this->mockEmailService,
            Container::instance()->get(RoleService::class),
            Container::instance()->get(UserService::class),
            Container::instance()->get(ActiveRecordInterceptor::class));

        $this->pendingActionService = Container::instance()->get(PendingActionService::class);
        $this->userService = MockObjectProvider::instance()->getMockInstance(UserService::class);

        $this->accountService = Container::instance()->get(AccountService::class);


    }


    public function testCanSearchForAccountsOptionallyLimitedByAccountNameStringAndPaging() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $matches = $this->accountService->searchForAccounts();

        $this->assertEquals(5, sizeof($matches));
        $this->assertEquals(AccountSummary::fetch(2), $matches[0]);
        $this->assertEquals(AccountSummary::fetch(1), $matches[1]);
        $this->assertEquals(AccountSummary::fetch(3), $matches[2]);
        $this->assertEquals(AccountSummary::fetch(5), $matches[3]);
        $this->assertEquals(AccountSummary::fetch(4), $matches[4]);

        $matches = $this->accountService->searchForAccounts("smart");
        $this->assertEquals(2, sizeof($matches));
        $this->assertEquals(AccountSummary::fetch(3), $matches[0]);
        $this->assertEquals(AccountSummary::fetch(5), $matches[1]);


        $matches = $this->accountService->searchForAccounts("", 2);
        $this->assertEquals(3, sizeof($matches));
        $this->assertEquals(AccountSummary::fetch(3), $matches[0]);
        $this->assertEquals(AccountSummary::fetch(5), $matches[1]);
        $this->assertEquals(AccountSummary::fetch(4), $matches[2]);

        $matches = $this->accountService->searchForAccounts("", 0, 3);
        $this->assertEquals(3, sizeof($matches));
        $this->assertEquals(AccountSummary::fetch(2), $matches[0]);
        $this->assertEquals(AccountSummary::fetch(1), $matches[1]);
        $this->assertEquals(AccountSummary::fetch(3), $matches[2]);


    }


    public function testCanCreateAccountWithoutAdminUser() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $accountId = $this->accountService->createAccount("Bernard Shaw");

        $this->assertNotNull($accountId);
        $account = Account::fetch($accountId);

        $this->assertEquals("Bernard Shaw", $account->getName());
        $this->assertEquals(Account::STATUS_ACTIVE, $account->getStatus());

    }


    public function testCanCreateAccountWithAdminUser() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $accountId = $this->accountService->createAccount("Bernard Shaw", "bernard@shaw.com", null, "Zoo Bernard");

        $this->assertNotNull($accountId);
        $account = Account::fetch($accountId);

        $this->assertEquals("Bernard Shaw", $account->getName());
        $this->assertEquals(Account::STATUS_ACTIVE, $account->getStatus());

        $adminUser = User::filter("WHERE name = 'Zoo Bernard'")[0];
        $this->assertEquals("bernard@shaw.com", $adminUser->getEmailAddress());
        $this->assertEquals([
            UserRole::fetch([$adminUser->getId(), Role::SCOPE_ACCOUNT, $account->getAccountId(), 0, $account->getAccountId()]),
            UserRole::fetch([$adminUser->getId(), Role::SCOPE_PROJECT, "*", 0, $account->getAccountId()])
        ], $adminUser->getRoles());
        $this->assertEquals(User::STATUS_ACTIVE, $adminUser->getStatus());

    }


    public function testCanCreateAccountWithSecurityDomains() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $accountId = $this->accountService->createAccount("Smith Consultancy", null, null, null, null, ["example.com", "test.org", "demo.info"]);

        $this->assertNotNull($accountId);
        $account = Account::fetch($accountId);

        $this->assertEquals(3, sizeof($account->getSecurityDomains()));
        $this->assertEquals(["example.com", "test.org", "demo.info"], ObjectArrayUtils::getMemberValueArrayForObjects("domainName", $account->getSecurityDomains()));

    }

    public function testCanCreateSubAccountAsParentAccountAdmin() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $accountId = $this->accountService->createAccount("Badger", null, null, null, 1);

        $this->assertNotNull($accountId);

        $account = Account::fetch($accountId);

        $this->assertEquals("Badger", $account->getName());


    }


    public function testCanChangeAccountNameDirectlyWithoutPasswordValidationIfLoggedInAsAdminUser() {
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $accountId = $this->accountService->createAccount("Andrew Shaw");
        $this->accountService->changeAccountName("Fiona Shaw", null, $accountId);

        $reAccount = Account::fetch($accountId);
        $this->assertEquals("Fiona Shaw", $reAccount->getName());

    }


    public function testCanUpdateAccountLogoUrl() {

        AuthenticationHelper::login("admin@kinicart.com", "password");
        $accountId = $this->accountService->createAccount("Bonzo");

        $this->accountService->updateLogo("https://images.test.com/mylogo", $accountId);

        $reAccount = Account::fetch($accountId);
        $this->assertEquals("https://images.test.com/mylogo", $reAccount->getLogo());


    }


    public function testCanUpdateSecurityDomains() {

        AuthenticationHelper::login("admin@kinicart.com", "password");
        $accountId = $this->accountService->createAccount("Bonzo", null, null, null, null, [
            "example.com", "demo.co.uk"
        ]);

        $this->accountService->updateSecurityDomains(["honeybadger.org", "stage.com"], $accountId);

        $reAccount = Account::fetch($accountId);
        $this->assertEquals(["honeybadger.org", "stage.com"],
            ObjectArrayUtils::getMemberValueArrayForObjects("domainName", $reAccount->getSecurityDomains()));

    }


    public function testCanSuspendAndReactivateAccounts() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $accountId = $this->accountService->createAccount("Playpen Account");
        $this->assertEquals(Account::STATUS_ACTIVE, Account::fetch($accountId)->getStatus());

        $this->accountService->suspendAccount($accountId, "Bad activity on account");
        $this->assertEquals(Account::STATUS_SUSPENDED, Account::fetch($accountId)->getStatus());
        $lastActivity = Activity::filter("ORDER BY id DESC")[0];
        $this->assertEquals("Account Suspended", $lastActivity->getEvent());
        $this->assertEquals($accountId, $lastActivity->getAccountId());
        $this->assertEquals(["note" => "Bad activity on account"], $lastActivity->getData());

        $this->accountService->reactivateAccount($accountId, "Apology accepted");
        $this->assertEquals(Account::STATUS_ACTIVE, Account::fetch($accountId)->getStatus());
        $lastActivity = Activity::filter("ORDER BY id DESC")[0];
        $this->assertEquals("Account Reactivated", $lastActivity->getEvent());
        $this->assertEquals($accountId, $lastActivity->getAccountId());
        $this->assertEquals(["note" => "Apology accepted"], $lastActivity->getData());


    }


    public function testCannotInviteUsersToJoinAccountIfNotLoggedInAsSuperUserForAccount() {

        $this->authenticationService->logout();


        try {
            $this->accountService->inviteUserToAccount(1, "newuser@samdavisdesign.co.uk", [new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3])]);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            $this->assertTrue(true);
        }


        AuthenticationHelper::login("regularuser@smartcoasting.org", "password");

        try {
            $this->accountService->inviteUserToAccount(1, "newuser@samdavisdesign.co.uk", [new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3])]);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            $this->assertTrue(true);
        }


    }


    public function testCannotInviteExistingAccountUserToJoinAccount() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        try {

            $this->mockedAccountService->inviteUserToAccount(1, "sam@samdavisdesign.co.uk", [new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3])]);
            $this->fail("Should have thrown here");
        } catch (UserAlreadyAttachedToAccountException $e) {
            $this->assertTrue(true);
        }


        try {

            $this->mockedAccountService->inviteUserToAccount(1, "bob@twofactor.com", [new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3])]);
            $this->fail("Should have thrown here");
        } catch (UserAlreadyAttachedToAccountException $e) {
            $this->assertTrue(true);
        }

    }

    public function testCannotInviteUsersToJoinAccountIfAccountSecurityDomainsDefinedAndUserEmailDoesNotMatch() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $this->mockedAccountService->updateSecurityDomains(["samdavisdesign.co.uk"], 1);

        try {
            $this->mockedAccountService->inviteUserToAccount(1, "bobbery@twofactor.com", [new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3])]);
            $this->fail("Should have thrown here");
        } catch (InvalidUserEmailDomainException $e) {
            $this->assertTrue(true);
        }

        // Check valid one
        $this->mockedAccountService->inviteUserToAccount(1, "bobbery@samdavisdesign.co.uk", [new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3])]);


        $this->mockedAccountService->updateSecurityDomains([], 1);


    }


    public function testCanInviteNewUsersToJoinAccountIfLoggedInAsSuperUserForAccountWithPendingActionAndEmailSent() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Program an identifier return value when creating a pending action.
        $this->mockPendingActionService->returnValue("createPendingAction", "XXXYYYZZZ", ["USER_INVITE", 1,
            ["emailAddress" => "newuser@samdavisdesign.co.uk",
                "initialRoles" => [
                    new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3])
                ],
                "newUser" => true]]);


        // Should succeed.
        $this->mockedAccountService->inviteUserToAccount(1, "newuser@samdavisdesign.co.uk", [new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3])]);

        // Check pending action was created
        $this->assertTrue($this->mockPendingActionService->methodWasCalled("createPendingAction", ["USER_INVITE", 1,
            ["emailAddress" => "newuser@samdavisdesign.co.uk",
                "initialRoles" => [
                    new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3])
                ],
                "newUser" => true]]));

        // Check email was sent
        $targetEmail = new AccountTemplatedEmail(1, "security/invite-user", ["emailAddress" => "newuser@samdavisdesign.co.uk",
            "invitationCode" => "XXXYYYZZZ",
            "newUser" => true]);


        $this->assertTrue($this->mockEmailService->methodWasCalled("send", [$targetEmail, 1]));


    }


    public function testCanInviteExistingUsersOfOtherAccountsToJoinAccountIfLoggedInAsSuperUserForAccountWithPendingActionAndEmailSent() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Program an identifier return value when creating a pending action.
        $this->mockPendingActionService->returnValue("createPendingAction", "XXXYYYZZZ", ["USER_INVITE", 1,
            ["emailAddress" => "mary@shoppingonline.com",
                "initialRoles" => [
                    new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3]),
                    new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [4])
                ],
                "newUser" => false]]);


        $interceptor = Container::instance()->get(ActiveRecordInterceptor::class);


        // Simulate insecure - usually implemented by annotation.
        $interceptor->executeInsecure(function () {
            // Should succeed.
            $this->mockedAccountService->inviteUserToAccount(1, "mary@shoppingonline.com", [new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3]),
                new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [4])]);

        });


        // Check pending action was created
        $this->assertTrue($this->mockPendingActionService->methodWasCalled("createPendingAction", ["USER_INVITE", 1,
            ["emailAddress" => "mary@shoppingonline.com",
                "initialRoles" => [
                    new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3]),
                    new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [4])
                ],
                "newUser" => false]]));

        // Check email was sent
        $targetEmail = new AccountTemplatedEmail(1, "security/invite-user", ["emailAddress" => "mary@shoppingonline.com",
            "invitationCode" => "XXXYYYZZZ",
            "newUser" => false]);


        $this->assertTrue($this->mockEmailService->methodWasCalled("send", [$targetEmail, 1]));


    }


    public function testCanGetActiveInvitationEmailAddressesForAnAccount() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $this->mockPendingActionService->returnValue("getAllPendingActionsForTypeAndObjectId", [
            new PendingAction("USER_INVITE", 1, ["emailAddress" => "sam@mydomain.com"]),
            new PendingAction("USER_INVITE", 1, ["emailAddress" => "mark@mydomain.com"]),
        ], ["USER_INVITE", 1]);

        $activeEmails = $this->mockedAccountService->getActiveAccountInvitationEmailAddresses(1);

        $this->assertEquals([
            new AccountInvitation(1, "sam@mydomain.com", $activeEmails[0]->getExpiryDate()),
            new AccountInvitation(1, "mark@mydomain.com", $activeEmails[1]->getExpiryDate())
        ], $activeEmails);

    }


    public function testCanResendInvitationEmailForActiveInvitation() {
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $resendAction = new PendingAction("USER_INVITE", 1, ["emailAddress" => "sam@mydomain.com", "newUser" => false]);

        $this->mockPendingActionService->returnValue("getAllPendingActionsForTypeAndObjectId", [
            $resendAction,
            new PendingAction("USER_INVITE", 1, ["emailAddress" => "mark@mydomain.com"]),
        ], ["USER_INVITE", 1]);

        $this->mockedAccountService->resendActiveAccountInvitationEmail("sam@mydomain.com", 1);


        // Check email was sent
        $targetEmail = new AccountTemplatedEmail(1, "security/invite-user", ["emailAddress" => "sam@mydomain.com",
            "invitationCode" => $resendAction->getIdentifier(),
            "newUser" => false,
            "resent" => true,
            "currentTime" => date("d/m/Y H:i:s")]);


        $this->assertTrue($this->mockEmailService->methodWasCalled("send", [$targetEmail, 1]));


    }


    public function testCanRevokeInvitationForAccount() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $inviteAction = new PendingAction("USER_INVITE", 1, ["emailAddress" => "mark@mydomain.com"]);
        $otherInviteAction = new PendingAction("USER_INVITE", 1, ["emailAddress" => "test@test.com"]);

        $this->mockPendingActionService->returnValue("getAllPendingActionsForTypeAndObjectId", [
            $inviteAction,
            $otherInviteAction
        ], ["USER_INVITE", 1]);

        $this->mockedAccountService->revokeActiveAccountInvitationEmail("mark@mydomain.com", 1);

        $this->assertTrue($this->mockPendingActionService->methodWasCalled("removePendingAction", ["USER_INVITE", $inviteAction->getIdentifier()]));
        $this->assertFalse($this->mockPendingActionService->methodWasCalled("removePendingAction", ["USER_INVITE", $otherInviteAction->getIdentifier()]));

    }


    public function testValidationExceptionThrownIfInvalidInvitationCodeSuppliedToAcceptUserInvitation() {

        $this->authenticationService->logout();

        try {
            $this->accountService->acceptUserInvitationForAccount("THISISABADONE");
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["invitationCode"]));
        }
    }


    public function testValidationExceptionThrownIfValidInvitationCodeSuppliedButNoPasswordForANewUser() {

        $this->authenticationService->logout();

        $invitationCode = $this->pendingActionService->createPendingAction("USER_INVITE", 1,
            ["emailAddress" => "newuser@samdavisdesign.co.uk",
                "initialRoles" => [
                    new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3])
                ],
                "newUser" => true]);

        try {
            $this->accountService->acceptUserInvitationForAccount($invitationCode);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["hashedPassword"]));
        }
    }


    public function testNewUserCreatedCorrectlyAndUserWelcomeEmailSentIfValidInvitationCodeAndPassword() {

        $this->authenticationService->logout();

        $invitationCode = $this->pendingActionService->createPendingAction("USER_INVITE", 1,
            ["emailAddress" => "newuser@samdavisdesign.co.uk",
                "initialRoles" => [
                    new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3])
                ],
                "newUser" => true]);


        $this->mockPendingActionService->returnValue("getPendingActionByIdentifier", $this->pendingActionService->getPendingActionByIdentifier("USER_INVITE", $invitationCode), [
            "USER_INVITE", $invitationCode
        ]);


        $interceptor = Container::instance()->get(ActiveRecordInterceptor::class);


        // Simulate insecure - usually implemented by annotation.
        $interceptor->executeInsecure(function () use ($invitationCode) {

            $this->mockedAccountService->acceptUserInvitationForAccount($invitationCode, AuthenticationHelper::hashNewPassword("helloJeffrey1"));

        });

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $newUsers = User::filter("WHERE emailAddress = ? AND parent_account_id = ?", "newuser@samdavisdesign.co.uk", 0);
        $newUser = $newUsers[0];

        $this->assertEquals(AuthenticationHelper::hashNewPassword("helloJeffrey1"), $newUser->getHashedPassword());
        $this->assertEquals(1, sizeof($newUser->getRoles()));
        $this->assertEquals(3, $newUser->getRoles()[0]->getRoleId());

        // Check email was sent
        $targetEmail = new UserTemplatedEmail($newUser->getId(), "security/invited-user-welcome", ["emailAddress" => "newuser@samdavisdesign.co.uk"]);

        $this->assertTrue($this->mockEmailService->methodWasCalled("send", [$targetEmail, 1, $newUser->getId()]));


    }


    public function testExistingUserAddedToAccountCorrectlyIfValidInvitationCodeAndPasswordAndNoEmailSent() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $user = new User("existing@test.com", AuthenticationHelper::hashNewPassword("Password12345"));
        $user->setRoles([
            new UserRole(Role::SCOPE_ACCOUNT, 5, 3, 2),
            new UserRole(Role::SCOPE_ACCOUNT, 6, 3, 3)
        ]);
        $user->setStatus(User::STATUS_ACTIVE);

        $user->save();

        $this->authenticationService->logout();

        $invitationCode = $this->pendingActionService->createPendingAction("USER_INVITE", 4,
            ["emailAddress" => "existing@test.com",
                "initialRoles" => [
                    new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 4, [3])
                ],
                "newUser" => false]);


        $this->mockPendingActionService->returnValue("getPendingActionByIdentifier", $this->pendingActionService->getPendingActionByIdentifier("USER_INVITE", $invitationCode), [
            "USER_INVITE", $invitationCode
        ]);


        $interceptor = Container::instance()->get(ActiveRecordInterceptor::class);


        // Simulate insecure - usually implemented by annotation.
        $interceptor->executeInsecure(function () use ($invitationCode) {
            $this->mockedAccountService->acceptUserInvitationForAccount($invitationCode);
        });

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $newUsers = User::filter("WHERE emailAddress = ? AND parent_account_id = ?", "existing@test.com", 0);
        $newUser = $newUsers[0];

        $this->assertEquals(3, sizeof($newUser->getRoles()));
        $this->assertEquals(4, $newUser->getRoles()[0]->getAccountId());
        $this->assertEquals(3, $newUser->getRoles()[0]->getRoleId());

        $this->assertFalse($this->mockEmailService->methodWasCalled("send"));

    }


    public function testCanRemoveUserFromAccountIfAccountSuperUserAndAllRolesAreRemovedForThatAccount() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $user = new User("existing2@test.com", AuthenticationHelper::hashNewPassword("Password12345"));
        $user->setRoles([
            new UserRole(Role::SCOPE_ACCOUNT, 1, 3, 1),
            new UserRole(Role::SCOPE_ACCOUNT, 5, 3, 2),
            new UserRole(Role::SCOPE_ACCOUNT, 6, 3, 3)
        ]);
        $user->setStatus(User::STATUS_ACTIVE);

        $user->save();


        $this->authenticationService->logout();

        // Check can't delete user from account
        try {
            $this->accountService->removeUserFromAccount(1, $user->getId());
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            $this->assertTrue(true);
        }


        AuthenticationHelper::login("regularuser@smartcoasting.org", "password");

        // Check can't delete user from account
        try {
            $this->accountService->removeUserFromAccount(1, $user->getId());
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            $this->assertTrue(true);
        }


        // Check can delete user from account if superuser
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->accountService->removeUserFromAccount(1, $user->getId());

        // Now try and get the user again
        try {
            User::fetch($user->getId());
            $this->fail("Should no longer have access");
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }

        // Now log in as super user
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $user = User::fetch($user->getId());
        $this->assertEquals(2, sizeof($user->getRoles()));


    }

    public function testCanGetAndSaveAccountSettings() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $this->assertEquals([], $this->accountService->getAccountSettings(1));

        $this->accountService->updateAccountSettings(["key1" => "Hello World", "key2" => "Good call", "key3" => "My one"]);

        $this->assertEquals(["key1" => "Hello World", "key2" => "Good call", "key3" => "My one"], $this->accountService->getAccountSettings(1));


    }

    public function testCanGetAndUpdateAccountDiscoverySettings() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        // Check initial state
        $this->assertEquals(new AccountDiscoveryItem("Peter Jones Car Washing", false, "SHAREWITHME2"), $this->accountService->getAccountDiscoverySettings(2));

        // Generate external key
        $key = $this->accountService->generateAccountExternalIdentifier(2);
        $this->assertNotNull($key);

        // Check saved correctly
        $externalKey = Account::fetch(2)->getExternalIdentifier();
        $this->assertEquals($key, $externalKey);

        $this->assertEquals(new AccountDiscoveryItem("Peter Jones Car Washing", false, $externalKey), $this->accountService->getAccountDiscoverySettings(2));


        // Make discoverable
        $this->accountService->setAccountDiscoverable(true, 2);
        $this->assertEquals(new AccountDiscoveryItem("Peter Jones Car Washing", true, $externalKey), $this->accountService->getAccountDiscoverySettings(2));


        // Make undiscoverable
        $this->accountService->setAccountDiscoverable(false);
        $this->assertEquals(new AccountDiscoveryItem("Peter Jones Car Washing", false, $externalKey), $this->accountService->getAccountDiscoverySettings(2));

        // Set external key to null manually and check discoverable generates a new external key.
        $account = Account::fetch(2);
        $account->setExternalIdentifier(null);
        $account->save();

        $this->accountService->setAccountDiscoverable(true);
        $externalKey = Account::fetch(2)->getExternalIdentifier();
        $this->assertNotNull($externalKey);
        $this->assertEquals(new AccountDiscoveryItem("Peter Jones Car Washing", true, $externalKey), $this->accountService->getAccountDiscoverySettings(2));

        $this->accountService->unsetAccountExternalIdentifier();
        $externalKey = Account::fetch(2)->getExternalIdentifier();
        $this->assertNull($externalKey);


    }

    public function testCanSearchForDiscoverableAccountsExcludingOwnAccount() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $account1Discoverable = new AccountDiscoveryItem("Sam Davis Design", true,
            $this->accountService->getAccount(1)->getExternalIdentifier());

        $account2Discoverable = new AccountDiscoveryItem("Peter Jones Car Washing", true,
            $this->accountService->getAccount(2)->getExternalIdentifier());

        $account3Discoverable = new AccountDiscoveryItem("Smart Coasting", true,
            $this->accountService->getAccount(3)->getExternalIdentifier());

        $account4Discoverable = new AccountDiscoveryItem("Smart Coasting - Design Account", true,
            $this->accountService->getAccount(5)->getExternalIdentifier());


        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");


        $this->assertEquals([$account2Discoverable, $account3Discoverable, $account4Discoverable],
            $this->accountService->searchForDiscoverableAccounts(""));

        // Filtered
        $this->assertEquals([$account3Discoverable, $account4Discoverable],
            $this->accountService->searchForDiscoverableAccounts("sm"));


        // Offset and limits
        $this->assertEquals([$account3Discoverable, $account4Discoverable],
            $this->accountService->searchForDiscoverableAccounts("", 1));

        $this->assertEquals([$account2Discoverable, $account3Discoverable],
            $this->accountService->searchForDiscoverableAccounts("", 0, 2));


    }


    public function testCanLookupDiscoverableAccountByExternalIdentifier() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $this->accountService->setAccountDiscoverable(true, 1);
        $this->accountService->setAccountDiscoverable(true, 2);
        $this->accountService->setAccountDiscoverable(true, 3);

        $account1Discoverable = new AccountDiscoveryItem("Sam Davis Design", true,
            $this->accountService->getAccount(1)->getExternalIdentifier());

        $account2Discoverable = new AccountDiscoveryItem("Peter Jones Car Washing", true,
            $this->accountService->getAccount(2)->getExternalIdentifier());

        $account3Discoverable = new AccountDiscoveryItem("Smart Coasting", true,
            $this->accountService->getAccount(3)->getExternalIdentifier());


        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $this->assertEquals($account1Discoverable, $this->accountService->lookupDiscoverableAccountByExternalIdentifier($account1Discoverable->getExternalIdentifier()));
        $this->assertEquals($account2Discoverable, $this->accountService->lookupDiscoverableAccountByExternalIdentifier($account2Discoverable->getExternalIdentifier()));
        $this->assertEquals($account3Discoverable, $this->accountService->lookupDiscoverableAccountByExternalIdentifier($account3Discoverable->getExternalIdentifier()));

        try {
            $this->accountService->lookupDiscoverableAccountByExternalIdentifier("BADBOY");
            $this->fail("Should have thrown here");
        } catch (ItemNotFoundException $e) {
        }


    }

    public function testCanGetAccountByExternalIdentifier() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        try {
            $this->accountService->getAccountByExternalIdentifier("12345678899");
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // Success
        }


        // Valid one
        $account3 = $this->accountService->getAccountByExternalIdentifier("SHAREWITHME3");
        $this->assertEquals(Account::fetch(3), $account3);

    }

}
