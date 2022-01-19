<?php

namespace Kiniauth\Test\Services\Account;

use Kiniauth\Bootstrap;
use Kiniauth\Exception\Security\InvalidAccountForUserException;
use Kiniauth\Exception\Security\InvalidLoginException;
use Kiniauth\Exception\Security\InvalidUserAccessTokenException;
use Kiniauth\Exception\Security\TooManyUserAccessTokensException;
use Kiniauth\Exception\Security\TwoFactorAuthenticationRequiredException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Communication\Email\StoredEmail;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserAccessToken;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Objects\Workflow\PendingAction;
use Kiniauth\Services\Application\BootstrapService;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Security\AssignedRole;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Security\Hash\SHA512HashProvider;
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

    /**
     * @var ObjectBinder
     */
    private $objectBinder;


    public function setUp(): void {
        parent::setUp();
        $this->userService = Container::instance()->get(\Kiniauth\Services\Account\UserService::class);
        $this->authenticationService = Container::instance()->get(\Kiniauth\Services\Security\AuthenticationService::class);
        $this->session = Container::instance()->get(Session::class);
        $this->pendingActionService = Container::instance()->get(PendingActionService::class);
        $this->objectBinder = Container::instance()->get(ObjectBinder::class);
    }

    /**
     * Create a user with a brand new account.
     */
    public function testCanCreateUserWithABrandNewAccount() {

        $this->authenticationService->logout();

        // Simple one with just email address and password.
        $activationCode = $this->userService->createPendingUserWithAccount("john@test.com", AuthenticationHelper::hashNewPassword("Helloworld1"));

        $pendingitem = $this->pendingActionService->getPendingActionByIdentifier("USER_ACTIVATION", $activationCode);

        $newUser = $this->objectBinder->bindFromArray($pendingitem->getData()["user"], User::class, false);

        $this->assertNull($newUser->getId());
        $this->assertEquals("john@test.com", $newUser->getEmailAddress());
        $this->assertEquals(AuthenticationHelper::hashNewPassword("Helloworld1"), $newUser->getHashedPassword());
        $this->assertEquals(0, $newUser->getParentAccountId());
        $this->assertEquals(User::STATUS_PENDING, $newUser->getStatus());
        $this->assertEquals(0, sizeof($newUser->getRoles()));


        // Now do one with a users name, check propagation to account name.
        // Simple one with just email address and password.
        $activationCode = $this->userService->createPendingUserWithAccount("john2@test.com", AuthenticationHelper::hashNewPassword("Helloworld1"), "John Smith");

        $pendingitem = $this->pendingActionService->getPendingActionByIdentifier("USER_ACTIVATION", $activationCode);

        $newUser = $this->objectBinder->bindFromArray($pendingitem->getData()["user"], User::class, false);


        $this->assertNull($newUser->getId());
        $this->assertEquals("john2@test.com", $newUser->getEmailAddress());
        $this->assertEquals("John Smith", $newUser->getName());
        $this->assertEquals(AuthenticationHelper::hashNewPassword("Helloworld1"), $newUser->getHashedPassword());
        $this->assertEquals(0, $newUser->getParentAccountId());
        $this->assertEquals(User::STATUS_PENDING, $newUser->getStatus());
        $this->assertEquals(0, sizeof($newUser->getRoles()));


        // Now do one with a user and account name, check propagation to account name.
        // Simple one with just email address and password.
        $activationCode = $this->userService->createPendingUserWithAccount("john3@test.com", AuthenticationHelper::hashNewPassword("Helloworld1"), "John Smith",
            "Smith Enterprises");

        $pendingitem = $this->pendingActionService->getPendingActionByIdentifier("USER_ACTIVATION", $activationCode);

        $newUser = $this->objectBinder->bindFromArray($pendingitem->getData()["user"], User::class, false);


        $this->assertNull($newUser->getId());
        $this->assertEquals("john3@test.com", $newUser->getEmailAddress());
        $this->assertEquals("John Smith", $newUser->getName());
        $this->assertEquals(AuthenticationHelper::hashNewPassword("Helloworld1"), $newUser->getHashedPassword());
        $this->assertEquals(0, $newUser->getParentAccountId());
        $this->assertEquals(User::STATUS_PENDING, $newUser->getStatus());
        $this->assertEquals(0, sizeof($newUser->getRoles()));

        // Activate john3
        $this->userService->activateAccount($activationCode);


        $pendingItems = PendingAction::values("COUNT(*)")[0];

        // Check duplicate fails silently and sends email to user
        $activationCode = $this->userService->createPendingUserWithAccount("john3@test.com", "helloworld", "John Smith",
            "Smith Enterprises");

        // Check no code returned and no pending item created
        $this->assertNull($activationCode);
        $this->assertEquals($pendingItems, PendingAction::values("COUNT(*)")[0]);

        // Login as admin to ensure permissions.
        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Check for an account exists email
        $lastEmail = StoredEmail::filter("ORDER BY id DESC")[0];

        $this->assertEquals(["John Smith <john3@test.com>"], $lastEmail->getRecipients());
        $this->assertEquals("Registration for a Kiniauth Example account", $lastEmail->getSubject());

        $this->authenticationService->logout();


        // Now do one with a user and account name and parent account id. check propagation to account name.
        // Simple one with just email address and password.
        $activationCode = $this->userService->createPendingUserWithAccount("john3@test.com", AuthenticationHelper::hashNewPassword("Helloworld1"), "John Smith",
            "Smith Enterprises", 1);

        $pendingitem = $this->pendingActionService->getPendingActionByIdentifier("USER_ACTIVATION", $activationCode);

        $newUser = $this->objectBinder->bindFromArray($pendingitem->getData()["user"], User::class, false);


        $this->assertNull($newUser->getId());
        $this->assertEquals("john3@test.com", $newUser->getEmailAddress());
        $this->assertEquals("John Smith", $newUser->getName());
        $this->assertEquals(AuthenticationHelper::hashNewPassword("Helloworld1"), $newUser->getHashedPassword());
        $this->assertEquals(1, $newUser->getParentAccountId());
        $this->assertEquals(User::STATUS_PENDING, $newUser->getStatus());

        $this->assertEquals(0, sizeof($newUser->getRoles()));


    }


    public function testActivationEmailSentAndPendingActionCreatedWhenCreatingNewUserWithAccount() {


        $this->authenticationService->logout();

        $newUser = $this->userService->createPendingUserWithAccount("john4@test.com", AuthenticationHelper::hashNewPassword("Helloworld1"), "John Smith",
            "Smythe Enterprises", 0);


        // Check for an action and grab the identifier
        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("USER_ACTIVATION", "NEW");
        $this->assertTrue(sizeof($pendingActions) > 0);
        $identifier = $pendingActions[0]->getIdentifier();


        // Login as admin to ensure permissions.
        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Check for an email containing the identifier
        $lastEmail = StoredEmail::filter("ORDER BY id DESC")[0];

        $this->assertEquals(["John Smith <john4@test.com>"], $lastEmail->getRecipients());
        $this->assertEquals("Activate your Kiniauth Example account", $lastEmail->getSubject());
        $this->assertStringContainsString($identifier, $lastEmail->getTextBody());


    }


    /**
     * Attempt account activation.
     *
     */
    public function testCanActivateAccountProvidedValidCodeSupplied() {

        $this->authenticationService->logout();

        $activationCode = $this->userService->createPendingUserWithAccount("john5@test.com", AuthenticationHelper::hashNewPassword(AuthenticationHelper::hashNewPassword("Helloworld1")), "John Smith",
            "Smythe Enterprises", 0);

        try {
            $this->userService->activateAccount("BADCODE");
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            // Success
        }


        // Login as admin to ensure permissions.
        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Check for an email containing the identifier
        $lastEmail = StoredEmail::filter("ORDER BY id DESC")[0];
        $this->assertNotEquals("Welcome to your Kiniauth Example account", $lastEmail->getSubject());

        // Activation should succeed.
        $this->userService->activateAccount($activationCode);

        // Check user is active
        $this->authenticationService->login("john5@test.com", AuthenticationHelper::encryptPasswordForLogin(AuthenticationHelper::hashNewPassword("Helloworld1")));
        $user = $this->session->__getLoggedInSecurable();
        $this->assertEquals("John Smith", $user->getName());
        $this->assertEquals(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertEquals(1, sizeof($user->getRoles()));
        $this->assertNotNull($user->getCreatedDate());


        $account = $this->session->__getLoggedInAccount();
        $this->assertEquals("Smythe Enterprises", $account->getName());
        $this->assertEquals($user->getRoles()[0]->getAccountId(), $account->getAccountId());
        $this->assertEquals(Account::STATUS_ACTIVE, $account->getStatus());
        $this->assertNotNull($account->getCreatedDate());


        // Login as admin to ensure permissions.
        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Check that welcome email was sent
        $lastEmail = StoredEmail::filter("ORDER BY id DESC")[0];
        $this->assertEquals("Welcome to your Kiniauth Example account", $lastEmail->getSubject());
        $this->assertEquals(["John Smith <john5@test.com>"], $lastEmail->getRecipients());


        $this->authenticationService->logout();

        // Check activation code is single use
        try {
            $this->userService->activateAccount($activationCode);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            // Success
        }

    }


    public function testCanGetAccountsForUser() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $newUser = new User("bobby@trials.com", "ashdjfhksalfjahfdgdgsdgsdfgfgddfgdsgsdgdsgdsgsdggfsdgsdgdgdgfgds", "Bobby Test", 0);
        $newUser->save();

        $newAccount = new Account("Trials inc", 0);
        $newAccount->save();

        $newAccount2 = new Account("Bongo LTD", 0);
        $newAccount2->save();

        $this->assertEquals([], $this->userService->getUserAccounts($newUser->getId()));

        $role = new UserRole(Role::SCOPE_ACCOUNT, $newAccount->getAccountId(), 0, $newAccount->getAccountId(), $newUser->getId());
        $role->save();
        $this->assertEquals([new AccountSummary($newAccount->getAccountId(), "Trials inc", 0)], $this->userService->getUserAccounts($newUser->getId()));

        $role = new UserRole(Role::SCOPE_ACCOUNT, $newAccount2->getAccountId(), 0, $newAccount2->getAccountId(), $newUser->getId());
        $role->save();
        $this->assertEquals([new AccountSummary($newAccount2->getAccountId(), "Bongo LTD", 0), new AccountSummary($newAccount->getAccountId(), "Trials inc", 0)], $this->userService->getUserAccounts($newUser->getId()));


        $newAccount->remove();
        $newAccount2->remove();
        $newUser->remove();

    }


    public function testCanSwitchAccountForUserProvidedAccessToAccount() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $newUser = new User("bobby@trials.com", hash("sha512", "passwordbobby@trials.com"), "Bobby Test", 0);
        $newUser->setStatus(User::STATUS_ACTIVE);
        $newUser->save();


        $newAccount = new Account("Trials inc", 0, Account::STATUS_ACTIVE);
        $newAccount->save();

        $newAccount2 = new Account("Bongo LTD", 0, Account::STATUS_ACTIVE);
        $newAccount2->save();

        $role = new UserRole(Role::SCOPE_ACCOUNT, $newAccount->getAccountId(), 0, $newAccount->getAccountId(), $newUser->getId());
        $role->save();

        $role = new UserRole(Role::SCOPE_ACCOUNT, $newAccount2->getAccountId(), 0, $newAccount2->getAccountId(), $newUser->getId());
        $role->save();


        AuthenticationHelper::login("bobby@trials.com", "password");

        try {
            $this->userService->switchActiveAccount(1, $newUser->getId());
            $this->fail("Should have thrown here");
        } catch (InvalidAccountForUserException $e) {

        }

        $this->userService->switchActiveAccount($newAccount2->getAccountId(), $newUser->getId());

        /**
         * @var User $reUser
         */
        $reUser = User::fetch($newUser->getId());
        $this->assertEquals($newAccount2->getAccountId(), $reUser->getActiveAccountId());
        $this->assertEquals($newAccount2->getAccountId(), $this->session->__getLoggedInSecurable()->getActiveAccountId());
        $this->assertEquals($newAccount2->getAccountId(), $this->session->__getLoggedInAccount()->getAccountId());


        $this->userService->switchActiveAccount($newAccount->getAccountId(), $newUser->getId());

        /**
         * @var User $reUser
         */
        $reUser = User::fetch($newUser->getId());
        $this->assertEquals($newAccount->getAccountId(), $reUser->getActiveAccountId());
        $this->assertEquals($newAccount->getAccountId(), $this->session->__getLoggedInSecurable()->getActiveAccountId());
        $this->assertEquals($newAccount->getAccountId(), $this->session->__getLoggedInAccount()->getAccountId());


        $newAccount->remove();
        $newAccount2->remove();
        $newUser->remove();

    }


    public function testCanUnlockAccountIfValidUnlockCodeProvided() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Set invalid login attempts to 5.
        $user = User::fetch(2);
        $user->setInvalidLoginAttempts(5);
        $user->save();

        // Lock Sam Davis
        $unlockCode = $this->userService->lockUser(2);

        try {
            $this->userService->unlockUser("fgjhsdkjgd");
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
        }

        $this->assertEquals(User::STATUS_LOCKED, User::fetch(2)->getStatus());


        $this->userService->unlockUser($unlockCode);

        $this->assertEquals(User::STATUS_ACTIVE, User::fetch(2)->getStatus());
        $this->assertEquals(0, User::fetch(2)->getInvalidLoginAttempts());


        try {
            $this->pendingActionService->getPendingActionByIdentifier("USER_LOCKED", $unlockCode);
            $this->fail("Should have removed action");
        } catch (ItemNotFoundException $e) {
        }

    }


    public function testCanCreateNewUserWithJustEmailAndRandomPasswordIsAssignedAndNewUserEmailed() {

        // Log out
        $this->authenticationService->logout();

        // Log in as super user.
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $userId = $this->userService->createUser("test@myshop.com");

        $this->assertNotNull($userId);
        $user = User::fetch($userId);

        $this->assertEquals("test@myshop.com", $user->getEmailAddress());
        $this->assertNotNull($user->getHashedPassword());
        $this->assertEquals([], $user->getRoles());

        // Check for an account exists email
        $lastEmail = StoredEmail::filter("ORDER BY id DESC")[0];

        $this->assertEquals(["test@myshop.com"], $lastEmail->getRecipients());
        $this->assertEquals("New Kiniauth Example user account created", $lastEmail->getSubject());
        $this->assertStringContainsString("<b>Email Address: </b>", $lastEmail->getTextBody());

    }


    public function testCanCreateNewUserWithEmailAndFixedHashedPasswordAndNoEmailSent() {

        // Log out
        $this->authenticationService->logout();

        // Log in as super user.
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $existingStoredEmails = sizeof(StoredEmail::filter("ORDER BY id DESC"));

        $password = hash("sha512", "MYTESTHASHEDPASSWORD");

        $userId = $this->userService->createUser("test@bingo.com", $password);

        $this->assertNotNull($userId);
        $user = User::fetch($userId);

        $this->assertEquals("test@bingo.com", $user->getEmailAddress());
        $this->assertEquals($password, $user->getHashedPassword());
        $this->assertEquals([], $user->getRoles());

        // Check for an account exists email
        $this->assertEquals($existingStoredEmails, sizeof(StoredEmail::filter("ORDER BY id DESC")));

    }


    public function testCanCreateNewUserWithNameAndRolesAsWell() {

        // Log out
        $this->authenticationService->logout();

        // Log in as super user.
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $existingStoredEmails = sizeof(StoredEmail::filter("ORDER BY id DESC"));

        $password = hash("sha512", "MYTESTHASHEDPASSWORD");

        $userId = $this->userService->createUser("test@bingo3.com", $password, "Zoom Man", [
            new UserRole(Role::SCOPE_ACCOUNT, 0, 0)]);

        $this->assertNotNull($userId);
        $user = User::fetch($userId);

        $this->assertEquals("test@bingo3.com", $user->getEmailAddress());
        $this->assertEquals($password, $user->getHashedPassword());
        $this->assertEquals("Zoom Man", $user->getName());
        $this->assertEquals([
            new UserRole(Role::SCOPE_ACCOUNT, 0, 0, null, $userId)], $user->getRoles());

        // Check for an account exists email
        $this->assertEquals($existingStoredEmails, sizeof(StoredEmail::filter("ORDER BY id DESC")));
    }


    public function testCanCreateNewAdminUserProvidedWeAreLoggedInAsSuperUser() {


        // Log out
        $this->authenticationService->logout();

        // Log in as super user.
        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Simple username / password one.
        $adminUserId = $this->userService->createAdminUser("marko@polo.com", AuthenticationHelper::hashNewPassword("Helloworld1"));

        $this->assertNotNull($adminUserId);
        $adminUser = User::fetch($adminUserId);
        $this->assertEquals("marko@polo.com", $adminUser->getEmailAddress());
        $this->assertEquals(AuthenticationHelper::hashNewPassword("Helloworld1"), $adminUser->getHashedPassword());
        $this->assertEquals(1, sizeof($adminUser->getRoles()));
        $this->assertEquals(0, $adminUser->getRoles()[0]->getScopeId());
        $this->assertEquals(0, $adminUser->getRoles()[0]->getRoleId());


        // Username, password and name one.
        $adminUserId = $this->userService->createAdminUser("marko2@polo.com", AuthenticationHelper::hashNewPassword("Helloworld1"), "Marko Polo");

        $this->assertNotNull($adminUserId);
        $adminUser = User::fetch($adminUserId);
        $this->assertEquals("marko2@polo.com", $adminUser->getEmailAddress());
        $this->assertEquals("Marko Polo", $adminUser->getName());
        $this->assertEquals(AuthenticationHelper::hashNewPassword("Helloworld1"), $adminUser->getHashedPassword());
        $this->assertEquals(1, sizeof($adminUser->getRoles()));
        $this->assertEquals(0, $adminUser->getRoles()[0]->getScopeId());
        $this->assertEquals(0, $adminUser->getRoles()[0]->getRoleId());


        // Check duplicate issue
        try {
            $this->userService->createAdminUser("marko2@polo.com", "pickle", "Marko Polo");

            $this->fail("Should have thrown validation problems here");

        } catch (ValidationException $e) {
            // Success
        }


    }


    public function testCanSearchForAccountUsers() {

        // Attempt a login. We need to be logged in to generate settings.
        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Default search
        $users = $this->userService->searchForUsers("");
        $this->assertEquals(10, sizeof($users["results"]));
        $this->assertTrue($users["totalRecords"] > 10);
        $this->assertEquals("Administrator", $users["results"][0]->getName());
        $this->assertEquals("James Smart Coasting", $users["results"][1]->getName());

        // Filtered search
        $users = $this->userService->searchForUsers("James");
        $this->assertEquals(2, sizeof($users["results"]));
        $this->assertEquals(2, $users["totalRecords"]);
        $this->assertEquals("James Smart Coasting", $users["results"][0]->getName());
        $this->assertEquals("James Smartcoasting", $users["results"][1]->getName());

        // Offset search
        $users = $this->userService->searchForUsers("James", 1);
        $this->assertEquals(1, sizeof($users["results"]));
        $this->assertEquals(2, $users["totalRecords"]);
        $this->assertEquals("James Smartcoasting", $users["results"][0]->getName());

        // Limit search
        $users = $this->userService->searchForUsers("James", 0, 1);
        $this->assertEquals(1, sizeof($users["results"]));
        $this->assertEquals(2, $users["totalRecords"]);
        $this->assertEquals("James Smart Coasting", $users["results"][0]->getName());


        // Account restricted search.
        $accountUsers = $this->userService->searchForUsers("", 0, 100, 1);
        $this->assertEquals(4, sizeof($accountUsers["results"]));
        $this->assertEquals("Regular User", $accountUsers["results"][0]->getName());
        $this->assertEquals("Sam Davis", $accountUsers["results"][1]->getName());


    }


    public function testCanGenerateTwoFactorSettingsForDefaultProvider() {
        // Attempt a login. We need to be logged in to generate settings.
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Check the user
        $loggedInUser = $this->session->__getLoggedInSecurable();
        $this->assertTrue($loggedInUser instanceof User);

        $twoFactorSettings = $this->userService->generateTwoFactorSettings();

        $this->assertNotNull($twoFactorSettings["secret"]);
        $this->assertNotNull($twoFactorSettings["qrCode"]);

    }


    public function testSendPasswordResetGeneratesAccountActionAndSendsEmailWithOneTimeCode() {

        $this->authenticationService->logout();

        $this->userService->sendPasswordReset("mary@shoppingonline.com");

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("PASSWORD_RESET", 7);
        $this->assertEquals(1, sizeof($pendingActions));
        $identifier = $pendingActions[0]->getIdentifier();

        $lastEmail = StoredEmail::filter("ORDER BY id DESC")[0];

        $this->assertEquals(["Mary Shopping <mary@shoppingonline.com>"], $lastEmail->getRecipients());
        $this->assertStringContainsString($identifier, $lastEmail->getTextBody());

    }


    public function testCanGetEmailForPasswordResetCodeOrExceptionIfNoneExists() {

        $this->userService->sendPasswordReset("mary@shoppingonline.com");

        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("PASSWORD_RESET", 7);
        $identifier = $pendingActions[0]->getIdentifier();

        $email = $this->userService->getEmailForPasswordResetCode($identifier);
        $this->assertEquals("mary@shoppingonline.com", $email);

        try {
            $this->userService->getEmailForPasswordResetCode("BADCODE");
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            // Success
        }

    }


    public function testChangePasswordThrowsValidationExceptionIfInvalidResetCodeOrPasswordSupplied() {

        $this->authenticationService->logout();

        try {
            $this->userService->changePassword("BADRESET", AuthenticationHelper::hashNewPassword("Helloworld1"));
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

        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Check old password still valid
        $user = new User("passwordchange@test.com", AuthenticationHelper::hashNewPassword("Helloworld0passwordchange@test.com"), "Password Change");
        $user->setStatus(User::STATUS_ACTIVE);
        $user->save();

        AuthenticationHelper::login("passwordchange@test.com", "Helloworld0");

        // Logout
        $this->authenticationService->logout();

        // Do reset
        $this->userService->sendPasswordReset("passwordchange@test.com");

        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("PASSWORD_RESET", $user->getId());
        $identifier = $pendingActions[0]->getIdentifier();

        // Now change password
        $this->userService->changePassword($identifier, AuthenticationHelper::hashNewPassword("Helloworld1passwordchange@test.com"));

        // Now confirm login
        AuthenticationHelper::login("passwordchange@test.com", "Helloworld1");


        // Now ensure we can't reuse the identifier.
        try {
            $this->userService->changePassword($identifier, "Helloworld2");
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            // Success
        }

        $this->assertTrue(true);

    }


    public function testCanGetAllUsersWithRole() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $accountUsers = $this->userService->getUsersWithRole(Role::SCOPE_ACCOUNT, 2);

        $this->assertEquals(3, sizeof($accountUsers));
        $this->assertEquals(User::fetch(3), $accountUsers[0]);
        $this->assertEquals(User::fetch(4), $accountUsers[1]);
        $this->assertEquals(User::fetch(7), $accountUsers[2]);


        $accountUsersWithRole = $this->userService->getUsersWithRole(Role::SCOPE_ACCOUNT, 2, 3);
        $this->assertEquals(1, sizeof($accountUsersWithRole));
        $this->assertEquals(User::fetch(7), $accountUsersWithRole[0]);


    }


    public function testCannotCreateUserAccessTokenForAccountWithInvalidLogin() {

        try {
            $this->userService->createUserAccessToken("dodgy", "pass");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
            // Success
        }

        try {
            $this->userService->createUserAccessToken("bob@twofactor.com", AuthenticationHelper::encryptPasswordForLogin("passwordbob@twofactor.com"));
            $this->fail("Should have thrown here");
        } catch (TwoFactorAuthenticationRequiredException $e) {
            // Success
        }


        $this->assertTrue(true);
    }


    public function testCanCreateUserAccessTokenForAccountWithValidLogin() {

        // Get the token
        $token = $this->userService->createUserAccessToken("sam@samdavisdesign.co.uk", AuthenticationHelper::encryptPasswordForLogin("passwordsam@samdavisdesign.co.uk"));

        $this->assertEquals(32, strlen($token));

        // Check it is stored
        $userAccessToken = UserAccessToken::fetch([2, AuthenticationHelper::hashNewPassword($token)]);
        $this->assertTrue($userAccessToken instanceof UserAccessToken);

    }

    public function testCanOnlyCreateUserAccessTokensUpToMaximumInConfigurationFile() {

        // Max access tokens
        Configuration::instance()->addParameter("max.useraccess.tokens", 1);

        try {
            $this->userService->createUserAccessToken("sam@samdavisdesign.co.uk", AuthenticationHelper::encryptPasswordForLogin("passwordsam@samdavisdesign.co.uk"));
            $this->fail("Should have thrown here");
        } catch (TooManyUserAccessTokensException $e) {
            // Success
        }

        // Increase max
        Configuration::instance()->addParameter("max.useraccess.tokens", 2);

        // Should be able to create one more
        $this->userService->createUserAccessToken("sam@samdavisdesign.co.uk", AuthenticationHelper::encryptPasswordForLogin("passwordsam@samdavisdesign.co.uk"));
        try {
            $this->userService->createUserAccessToken("sam@samdavisdesign.co.uk", AuthenticationHelper::encryptPasswordForLogin("passwordsam@samdavisdesign.co.uk"));
            $this->fail("Should have thrown here");
        } catch (TooManyUserAccessTokensException $e) {
            // Success
        }


        // Remove max, check it defaults to 5
        Configuration::instance()->removeParameter("max.useraccess.tokens");


        // Should be able to create three more
        $this->userService->createUserAccessToken("sam@samdavisdesign.co.uk", AuthenticationHelper::encryptPasswordForLogin("passwordsam@samdavisdesign.co.uk"));
        $this->userService->createUserAccessToken("sam@samdavisdesign.co.uk", AuthenticationHelper::encryptPasswordForLogin("passwordsam@samdavisdesign.co.uk"));
        $this->userService->createUserAccessToken("sam@samdavisdesign.co.uk", AuthenticationHelper::encryptPasswordForLogin("passwordsam@samdavisdesign.co.uk"));

        try {
            $this->userService->createUserAccessToken("sam@samdavisdesign.co.uk", AuthenticationHelper::encryptPasswordForLogin("passwordsam@samdavisdesign.co.uk"));
            $this->fail("Should have thrown here");
        } catch (TooManyUserAccessTokensException $e) {
            // Success
        }

        $this->assertTrue(true);

    }


    public function testCanAddSecondaryTokenToExistingUserAccessToken() {

        $token = $this->userService->createUserAccessToken("simon@peterjonescarwash.com", AuthenticationHelper::encryptPasswordForLogin("passwordsimon@peterjonescarwash.com"));

        try {
            $this->userService->addSecondaryTokenToUserAccessToken("BADTOKEN", "NEWSECONDARY");
            $this->fail("Should have thrown here");
        } catch (InvalidUserAccessTokenException $e) {
            // Success
        }


        $this->userService->addSecondaryTokenToUserAccessToken($token, "WONDERFULWORLD");

        // Check the hash has been updated.
        $userAccessToken = UserAccessToken::fetch([3, AuthenticationHelper::hashNewPassword($token . "--" . "WONDERFULWORLD")]);
        $this->assertTrue($userAccessToken instanceof UserAccessToken);

    }


}
