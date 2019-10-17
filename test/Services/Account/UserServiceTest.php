<?php

namespace Kiniauth\Test\Services\Account;

use Kiniauth\Bootstrap;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Email\StoredEmail;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Workflow\PendingAction;
use Kiniauth\Services\Application\BootstrapService;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Validation\ValidationException;

include_once __DIR__ . "/../../autoloader.php";

class UserServiceTest extends TestBase {

    /**
     * @var \Kiniauth\Services\Account\UserService
     */
    private $userService;

    /**
     * @var \Kiniauth\Services\Application\AuthenticationService
     */
    private $authenticationService;

    /**
     * @var PendingActionService
     */
    private $pendingActionService;


    /**
     * @var Session
     */
    private $session;


    public function setUp(): void {
        parent::setUp();
        $this->userService = Container::instance()->get(\Kiniauth\Services\Account\UserService::class);
        $this->authenticationService = Container::instance()->get(\Kiniauth\Services\Security\AuthenticationService::class);
        $this->session = Container::instance()->get(Session::class);
        $this->pendingActionService = Container::instance()->get(PendingActionService::class);
    }

    /**
     * Create a user with a brand new account.
     */
    public function testCanCreateUserWithABrandNewAccount() {

        $this->authenticationService->logout();

        // Simple one with just email address and password.
        $newUser = $this->userService->createWithAccount("john@test.com", "Helloworld1");

        $this->assertNotNull($newUser->getId());
        $this->assertEquals("john@test.com", $newUser->getEmailAddress());
        $this->assertEquals(hash("md5", "Helloworld1"), $newUser->getHashedPassword());
        $this->assertEquals(0, $newUser->getParentAccountId());
        $this->assertEquals(User::STATUS_PENDING, $newUser->getStatus());

        $this->assertEquals(1, sizeof($newUser->getRoles()));


        $this->assertEquals($newUser->getActiveAccountId(), $newUser->getRoles()[0]->getScopeId());
        $this->assertNull($newUser->getRoles()[0]->getRoleId());


        // Now do one with a users name, check propagation to account name.
        // Simple one with just email address and password.
        $newUser = $this->userService->createWithAccount("john2@test.com", "Helloworld1", "John Smith");

        $this->assertNotNull($newUser->getId());
        $this->assertEquals("john2@test.com", $newUser->getEmailAddress());
        $this->assertEquals("John Smith", $newUser->getName());
        $this->assertEquals(hash("md5", "Helloworld1"), $newUser->getHashedPassword());
        $this->assertEquals(0, $newUser->getParentAccountId());
        $this->assertEquals(User::STATUS_PENDING, $newUser->getStatus());

        $this->assertEquals(1, sizeof($newUser->getRoles()));


        // Now do one with a user and account name, check propagation to account name.
        // Simple one with just email address and password.
        $newUser = $this->userService->createWithAccount("john3@test.com", "Helloworld1", "John Smith",
            "Smith Enterprises");

        $this->assertNotNull($newUser->getId());
        $this->assertEquals("john3@test.com", $newUser->getEmailAddress());
        $this->assertEquals("John Smith", $newUser->getName());
        $this->assertEquals(hash("md5", "Helloworld1"), $newUser->getHashedPassword());
        $this->assertEquals(0, $newUser->getParentAccountId());
        $this->assertEquals(User::STATUS_PENDING, $newUser->getStatus());

        $this->assertEquals(1, sizeof($newUser->getRoles()));


        // Check duplicate issue
        try {
            $this->userService->createWithAccount("john3@test.com", "helloworld", "John Smith",
                "Smith Enterprises");

            $this->fail("Should have thrown validation problems here");

        } catch (ValidationException $e) {
            // Success
        }

        // Now do one with a user and account name and parent account id. check propagation to account name.
        // Simple one with just email address and password.
        $newUser = $this->userService->createWithAccount("john3@test.com", "Helloworld1", "John Smith",
            "Smith Enterprises", 1);

        $this->assertNotNull($newUser->getId());
        $this->assertEquals("john3@test.com", $newUser->getEmailAddress());
        $this->assertEquals("John Smith", $newUser->getName());
        $this->assertEquals(hash("md5", "Helloworld1"), $newUser->getHashedPassword());
        $this->assertEquals(1, $newUser->getParentAccountId());
        $this->assertEquals(User::STATUS_PENDING, $newUser->getStatus());

        $this->assertEquals(1, sizeof($newUser->getRoles()));


    }


    public function testActivationEmailSentAndPendingActionCreatedWhenCreatingNewUserWithAccount() {


        $this->authenticationService->logout();

        $newUser = $this->userService->createWithAccount("john4@test.com", "Helloworld1", "John Smith",
            "Smythe Enterprises", 0);


        // Check for an action and grab the identifier
        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("USER_ACTIVATION", $newUser->getId());
        $this->assertTrue(sizeof($pendingActions) > 0);
        $identifier = $pendingActions[0]->getIdentifier();


        // Login as admin to ensure permissions.
        $this->authenticationService->login("admin@kinicart.com", "password");

        // Check for an email containing the identifier
        $lastEmail = StoredEmail::filter("ORDER BY id DESC")[0];

        $this->assertEquals(["John Smith <john4@test.com>"], $lastEmail->getRecipients());
        $this->assertEquals("Activate your Kiniauth Example account", $lastEmail->getSubject());
        $this->assertStringContainsString($identifier, $lastEmail->getTextBody());


    }


    public function testCanCreateNewAdminUserProvidedWeAreLoggedInAsSuperUser() {


        // Log out
        $this->authenticationService->logout();

//        try {
//            $this->userService->createAdminUser("marko@polo.com", "pickle");
//            $this->fail("Should have thrown here");
//        } catch (AccessDeniedException $e) {
//            // Expected
//        }

        // Log in as super user.
        $this->authenticationService->login("admin@kinicart.com", "password");

        // Simple username / password one.
        $adminUser = $this->userService->createAdminUser("marko@polo.com", "Helloworld1");

        $this->assertNotNull($adminUser->getId());
        $this->assertEquals("marko@polo.com", $adminUser->getEmailAddress());
        $this->assertEquals(hash("md5", "Helloworld1"), $adminUser->getHashedPassword());
        $this->assertEquals(1, sizeof($adminUser->getRoles()));
        $this->assertEquals(0, $adminUser->getRoles()[0]->getScopeId());
        $this->assertNull($adminUser->getRoles()[0]->getRoleId());


        // Username, password and name one.
        $adminUser = $this->userService->createAdminUser("marko2@polo.com", "Helloworld1", "Marko Polo");

        $this->assertNotNull($adminUser->getId());
        $this->assertEquals("marko2@polo.com", $adminUser->getEmailAddress());
        $this->assertEquals("Marko Polo", $adminUser->getName());
        $this->assertEquals(hash("md5", "Helloworld1"), $adminUser->getHashedPassword());
        $this->assertEquals(1, sizeof($adminUser->getRoles()));
        $this->assertEquals(0, $adminUser->getRoles()[0]->getScopeId());
        $this->assertNull($adminUser->getRoles()[0]->getRoleId());


        // Check duplicate issue
        try {
            $this->userService->createAdminUser("marko2@polo.com", "pickle", "Marko Polo");

            $this->fail("Should have thrown validation problems here");

        } catch (ValidationException $e) {
            // Success
        }


    }

    public function testCanGenerateTwoFactorSettingsForDefaultProvider() {
        // Attempt a login. We need to be logged in to generate settings.
        $this->authenticationService->login("sam@samdavisdesign.co.uk", "password");

        // Check the user
        $loggedInUser = $this->session->__getLoggedInUser();
        $this->assertTrue($loggedInUser instanceof User);

        $twoFactorSettings = $this->userService->generateTwoFactorSettings();

        $this->assertNotNull($twoFactorSettings["secret"]);
        $this->assertNotNull($twoFactorSettings["qrCode"]);

    }


    public function testSendPasswordResetGeneratesAccountActionAndSendsEmailWithOneTimeCode() {

        $this->authenticationService->logout();

        $this->userService->sendPasswordReset("mary@shoppingonline.com");

        $this->authenticationService->login("admin@kinicart.com", "password");


        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("PASSWORD_RESET", 7);
        $this->assertEquals(1, sizeof($pendingActions));
        $identifier = $pendingActions[0]->getIdentifier();

        $lastEmail = StoredEmail::filter("ORDER BY id DESC")[0];

        $this->assertEquals(["mary@shoppingonline.com"], $lastEmail->getRecipients());
        $this->assertStringContainsString($identifier, $lastEmail->getTextBody());

    }


    public function testChangePasswordThrowsValidationExceptionIfInvalidResetCodeOrPasswordSupplied() {

        $this->authenticationService->logout();

        try {
            $this->userService->changePassword("BADRESET", "Helloworld1");
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            // Success
        }


        // Generate a real code
        $this->userService->sendPasswordReset("mary@shoppingonline.com");

        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("PASSWORD_RESET", 7);
        $identifier = $pendingActions[0]->getIdentifier();


        try {
            $this->userService->changePassword($identifier, "gfddgsgfdsg");
            $this->fail("Should have thrown her");
        } catch (ValidationException $e) {
            // Success
        }


        try {
            $this->userService->changePassword($identifier, "");
            $this->fail("Should have thrown her");
        } catch (ValidationException $e) {


            $this->pendingActionService->removePendingAction("PASSWORD_RESET", $identifier);

            $this->assertTrue(true);
        }

    }

    public function testCanChangePasswordIfValidPasswordAndCodeSupplied() {

        // Check old password still valid
        $user = new User("passwordchange@test.com", "Helloworld0", "Password Change");
        $user->setStatus(User::STATUS_ACTIVE);
        $user->save();

        $this->authenticationService->login("passwordchange@test.com", "Helloworld0");

        // Logout
        $this->authenticationService->logout();

        // Do reset
        $this->userService->sendPasswordReset("passwordchange@test.com");

        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("PASSWORD_RESET", $user->getId());
        $identifier = $pendingActions[0]->getIdentifier();

        // Now change password
        $this->userService->changePassword($identifier, "Helloworld1");

        // Now confirm login
        $this->authenticationService->login("passwordchange@test.com", "Helloworld1");


        // Now ensure we can't reuse the identifier.
        try {
            $this->userService->changePassword($identifier, "Helloworld2");
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            // Success
        }

        $this->assertTrue(true);

    }


    public
    function testCanGetAllUsersWithRole() {

        $accountUsers = $this->userService->getUsersWithRole(Role::SCOPE_ACCOUNT, 2);

        $this->assertEquals(3, sizeof($accountUsers));
        $this->assertEquals(User::fetch(3), $accountUsers[0]);
        $this->assertEquals(User::fetch(4), $accountUsers[1]);
        $this->assertEquals(User::fetch(7), $accountUsers[2]);


        $accountUsersWithRole = $this->userService->getUsersWithRole(Role::SCOPE_ACCOUNT, 2, 3);
        $this->assertEquals(1, sizeof($accountUsersWithRole));
        $this->assertEquals(User::fetch(7), $accountUsersWithRole[0]);


    }

}
