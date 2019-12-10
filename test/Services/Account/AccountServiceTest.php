<?php


namespace Kiniauth\Test\Services\Account;

use Kiniauth\Exception\Security\UserAlreadyAttachedToAccountException;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Security\AssignedRole;
use Kiniauth\ValueObjects\Security\ScopeObjectRolesAssignment;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;


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
            Container::instance()->get(UserService::class));

        $this->pendingActionService = Container::instance()->get(PendingActionService::class);
        $this->accountService = Container::instance()->get(AccountService::class);


    }


    public function testCannotInviteUsersToJoinAccountIfNotLoggedInAsSuperUserForAccount() {

        $this->authenticationService->logout();


        try {
            $this->accountService->inviteUserToAccount(1, "newuser@samdavisdesign.co.uk", [new AssignedRole(3, 1)]);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            $this->assertTrue(true);
        }


        $this->authenticationService->login("regularuser@smartcoasting.org", "password");

        try {
            $this->accountService->inviteUserToAccount(1, "newuser@samdavisdesign.co.uk", [new AssignedRole(3, 1)]);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            $this->assertTrue(true);
        }


    }


    public function testCannotInviteExistingAccountUserToJoinAccount() {

        $this->authenticationService->login("sam@samdavisdesign.co.uk", "password");

        try {

            $this->mockedAccountService->inviteUserToAccount(1, "sam@samdavisdesign.co.uk", [new AssignedRole(3, 1)]);
            $this->fail("Should have thrown here");
        } catch (UserAlreadyAttachedToAccountException $e) {
            $this->assertTrue(true);
        }


        try {

            $this->mockedAccountService->inviteUserToAccount(1, "regularuser@smartcoasting.org", [new AssignedRole(3, 1)]);
            $this->fail("Should have thrown here");
        } catch (UserAlreadyAttachedToAccountException $e) {
            $this->assertTrue(true);
        }

    }


    public function testCanInviteNewUsersToJoinAccountIfLoggedInAsSuperUserForAccountWithPendingActionAndEmailSent() {

        $this->authenticationService->login("sam@samdavisdesign.co.uk", "password");

        // Program an identifier return value when creating a pending action.
        $this->mockPendingActionService->returnValue("createPendingAction", "XXXYYYZZZ", ["USER_INVITE", 1,
            ["emailAddress" => "newuser@samdavisdesign.co.uk",
                "initialRoles" => [
                    new AssignedRole(3, 1)
                ],
                "newUser" => true]]);


        // Should succeed.
        $this->mockedAccountService->inviteUserToAccount(1, "newuser@samdavisdesign.co.uk", [new AssignedRole(3, 1)]);

        // Check pending action was created
        $this->assertTrue($this->mockPendingActionService->methodWasCalled("createPendingAction", ["USER_INVITE", 1,
            ["emailAddress" => "newuser@samdavisdesign.co.uk",
                "initialRoles" => [
                    new AssignedRole(3, 1)
                ],
                "newUser" => true]]));

        // Check email was sent
        $targetEmail = new AccountTemplatedEmail(1, "security/invite-user", ["emailAddress" => "newuser@samdavisdesign.co.uk",
            "invitationCode" => "XXXYYYZZZ",
            "newUser" => true]);


        $this->assertTrue($this->mockEmailService->methodWasCalled("send", [$targetEmail, 1]));


    }


    public function testCanInviteExistingUsersOfOtherAccountsToJoinAccountIfLoggedInAsSuperUserForAccountWithPendingActionAndEmailSent() {

        $this->authenticationService->login("sam@samdavisdesign.co.uk", "password");

        // Program an identifier return value when creating a pending action.
        $this->mockPendingActionService->returnValue("createPendingAction", "XXXYYYZZZ", ["USER_INVITE", 1,
            ["emailAddress" => "mary@shoppingonline.com",
                "initialRoles" => [
                    new AssignedRole(3, 1),
                    new AssignedRole(4, 1)
                ],
                "newUser" => false]]);


        $interceptor = Container::instance()->get(ActiveRecordInterceptor::class);


        // Simulate insecure - usually implemented by annotation.
        $interceptor->executeInsecure(function () {
            // Should succeed.
            $this->mockedAccountService->inviteUserToAccount(1, "mary@shoppingonline.com", [new AssignedRole(3, 1),
                new AssignedRole(4, 1)]);

        });


        // Check pending action was created
        $this->assertTrue($this->mockPendingActionService->methodWasCalled("createPendingAction", ["USER_INVITE", 1,
            ["emailAddress" => "mary@shoppingonline.com",
                "initialRoles" => [
                    new AssignedRole(3, 1),
                    new AssignedRole(4, 1)
                ],
                "newUser" => false]]));

        // Check email was sent
        $targetEmail = new AccountTemplatedEmail(1, "security/invite-user", ["emailAddress" => "mary@shoppingonline.com",
            "invitationCode" => "XXXYYYZZZ",
            "newUser" => false]);


        $this->assertTrue($this->mockEmailService->methodWasCalled("send", [$targetEmail, 1]));


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
                    new AssignedRole(3, 1)
                ],
                "newUser" => true]);

        try {
            $this->accountService->acceptUserInvitationForAccount($invitationCode);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["hashedPassword"]));
        }
    }


    public function testNewUserCreatedCorrectlyIfValidInvitationCodeAndPassword() {

        $this->authenticationService->logout();

        $invitationCode = $this->pendingActionService->createPendingAction("USER_INVITE", 1,
            ["emailAddress" => "newuser@samdavisdesign.co.uk",
                "initialRoles" => [
                    new ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [3])
                ],
                "newUser" => true]);


        $this->accountService->acceptUserInvitationForAccount($invitationCode, "helloJeffrey1");

        $this->authenticationService->login("admin@kinicart.com", "password");

        $newUsers = User::filter("WHERE emailAddress = ? AND parent_account_id = ?", "newuser@samdavisdesign.co.uk", 0);
        $newUser = $newUsers[0];

        $this->assertEquals(md5("helloJeffrey1"), $newUser->getHashedPassword());
        $this->assertEquals(1, sizeof($newUser->getRoles()));
        $this->assertEquals(3, $newUser->getRoles()[0]->getRoleId());

    }


    public function testExistingUserAddedToAccountCorrectlyIfValidInvitationCodeAndPassword() {

        $this->authenticationService->login("admin@kinicart.com", "password");

        $user = new User("existing@test.com", "Password12345");
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


        $this->accountService->acceptUserInvitationForAccount($invitationCode);

        $this->authenticationService->login("admin@kinicart.com", "password");

        $newUsers = User::filter("WHERE emailAddress = ? AND parent_account_id = ?", "existing@test.com", 0);
        $newUser = $newUsers[0];

        $this->assertEquals(3, sizeof($newUser->getRoles()));
        $this->assertEquals(4, $newUser->getRoles()[0]->getAccountId());
        $this->assertEquals(3, $newUser->getRoles()[0]->getRoleId());

    }

}
