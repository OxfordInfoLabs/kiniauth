<?php

namespace Kiniauth\Test\Objects\Application;

use Kiniauth\Bootstrap;
use Kiniauth\Exception\Security\AccountSuspendedException;
use Kiniauth\Exception\Security\InvalidAPICredentialsException;
use Kiniauth\Exception\Security\InvalidLoginException;
use Kiniauth\Exception\Security\InvalidReferrerException;
use Kiniauth\Exception\Security\InvalidUserAccessTokenException;
use Kiniauth\Exception\Security\UserSuspendedException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Account\UserAccountRole;
use Kiniauth\Objects\Communication\Email\StoredEmail;
use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserSession;
use Kiniauth\Objects\Security\UserSessionProfile;
use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Application\SettingsService;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Application\BootstrapService;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Security\TwoFactor\TwoFactorProvider;
use Kiniauth\Services\Security\UserSessionService;
use Kiniauth\Services\Workflow\PendingActionService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Security\Hash\HashProvider;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\MVC\Request\URL;

include_once __DIR__ . "/../../autoloader.php";

class AuthenticationServiceTest extends TestBase {

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var PendingActionService
     */
    private $pendingActionService;


    /**
     * @var MockObject
     */
    private $twoFactorProvider;


    public function setUp(): void {

        parent::setUp();

        Container::instance()->get(Bootstrap::class);

        $this->session = Container::instance()->get(Session::class);
        $this->pendingActionService = Container::instance()->get(PendingActionService::class);

        $this->twoFactorProvider = MockObjectProvider::instance()->getMockInstance(TwoFactorProvider::class);

        $this->authenticationService = new AuthenticationService(Container::instance()->get(SettingsService::class),
            $this->session, Container::instance()->get(SecurityService::class), $this->twoFactorProvider,
            Container::instance()->get(HashProvider::class), Container::instance()->get(UserService::class),
            Container::instance()->get(UserSessionService::class));


        // Assume two factor is not required
        $this->twoFactorProvider->returnValue("generateTwoFactorIfRequired", false);
    }

    public function testCanCheckWhetherEmailExistsOrNot() {

        $this->assertFalse($this->authenticationService->emailExists("james@test.com"));
        $this->assertFalse($this->authenticationService->emailExists("bobby@wrong.test"));
        $this->assertTrue($this->authenticationService->emailExists("admin@kinicart.com"));

    }


    /**
     * Check we can authenticate as a super user.
     */
    public function testCanLoginAsSuperUser() {

        // Attempt a login.
        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Confirm that we are now logged in
        $this->assertNull($this->session->__getLoggedInAccount());

        $loggedInUser = $this->session->__getLoggedInSecurable();
        $this->assertTrue($loggedInUser instanceof User);

        $this->assertEquals(1, $loggedInUser->getId());
        $this->assertEquals("Administrator", $loggedInUser->getName());
        $this->assertEquals(1, sizeof($loggedInUser->getRoles()));


    }

    /**
     * Check we can authenticate as a super user.
     */
    public function testCanLoginAsRegularAccount() {

        // Attempt a login.
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Check the user
        $loggedInUser = $this->session->__getLoggedInSecurable();
        $this->assertTrue($loggedInUser instanceof User);

        $this->assertEquals(2, $loggedInUser->getId());
        $this->assertEquals("Sam Davis", $loggedInUser->getName());
        $this->assertEquals("07891 147676", $loggedInUser->getMobileNumber());
        $this->assertEquals("samdavis@gmail.com", $loggedInUser->getBackupEmailAddress());

        $this->assertEquals(1, sizeof($loggedInUser->getRoles()));
        $this->assertEquals(1, $loggedInUser->getRoles()[0]->getAccountId());

        $loggedInAccount = $this->session->__getLoggedInAccount();
        $this->assertTrue($loggedInAccount instanceof AccountSummary);
        $this->assertEquals(1, $loggedInAccount->getAccountId());
        $this->assertEquals("Sam Davis Design", $loggedInAccount->getName());

    }

    public function testIfTwoFactorIsRequiredForClientSuppliedDataLoginIsInterrupted() {

        // Login as admin to read bob
        Container::instance()->get(ActiveRecordInterceptor::class)->executeInsecure(function () {

            $bob = User::fetch(11);

            $this->twoFactorProvider->returnValue("generateTwoFactorIfRequired", 987654321, [
                $bob, 123456
            ]);

            // Attempt a login.
            $attemptedLogin = $this->authenticationService->login("bob@twofactor.com",
                AuthenticationHelper::encryptPasswordForLogin("passwordbob@twofactor.com"), 123456, 0);

            $this->assertEquals("REQUIRES_2FA", $attemptedLogin);

            // Check pending user set
            $pendingUser = $this->session->__getPendingLoggedInUser();
            $this->assertTrue($pendingUser instanceof User);
            $this->assertEquals(11, $pendingUser->getId());

            // Check 2FA data stashed
            $this->assertEquals(987654321, $this->session->__getPendingTwoFactorData());


        });


    }


    public function testIfTwoFactorIsNotRequiredForClientSuppliedDataLoginContinues() {

        // Login as admin to read bob
        Container::instance()->get(ActiveRecordInterceptor::class)->executeInsecure(function () {

            $bob = User::fetch(11);

            $this->twoFactorProvider->returnValue("generateTwoFactorIfRequired", false, [
                $bob, 123456
            ]);

            // Attempt a login.
            $attemptedLogin = $this->authenticationService->login("bob@twofactor.com",
                AuthenticationHelper::encryptPasswordForLogin("passwordbob@twofactor.com"), 123456, 0);

            $this->assertEquals("LOGGED_IN", $attemptedLogin);

        });


    }


    public function testCanLoginAsUserWithPrescribedActiveAccount() {

        AuthenticationHelper::login("james@smartcoasting.org", "password");

        // Check the user
        $loggedInUser = $this->session->__getLoggedInSecurable();
        $this->assertTrue($loggedInUser instanceof User);

        $this->assertEquals(4, $loggedInUser->getId());

        $this->assertEquals(2, sizeof($loggedInUser->getRoles()));
        $this->assertEquals(2, $loggedInUser->getRoles()[0]->getAccountId());
        $this->assertEquals(3, $loggedInUser->getRoles()[1]->getAccountId());

        $loggedInAccount = $this->session->__getLoggedInAccount();
        $this->assertTrue($loggedInAccount instanceof AccountSummary);
        $this->assertEquals(3, $loggedInAccount->getAccountId());
        $this->assertEquals("Smart Coasting", $loggedInAccount->getName());

    }


    public function testCanLoginAsSubUserOfParentAccountIfParentAccountContextActive() {


        // Activate parent context.
        $this->authenticationService->updateActiveParentAccount(new URL("http://samdavis.org/mark"));

        AuthenticationHelper::login("james@smartcoasting.org", "password");

        // Check the user
        $loggedInUser = $this->session->__getLoggedInSecurable();
        $this->assertTrue($loggedInUser instanceof User);

        $this->assertEquals(9, $loggedInUser->getId());

        $this->assertEquals(1, sizeof($loggedInUser->getRoles()));
        $this->assertEquals(5, $loggedInUser->getRoles()[0]->getAccountId());


        $loggedInAccount = $this->session->__getLoggedInAccount();
        $this->assertTrue($loggedInAccount instanceof AccountSummary);
        $this->assertEquals(5, $loggedInAccount->getAccountId());
        $this->assertEquals("Smart Coasting - Design Account", $loggedInAccount->getName());

        // Reset parent context.
        $this->authenticationService->updateActiveParentAccount(new URL("https://kinicart.example/hello/123"));


    }


    public function testAccountLockedIfMaxLoginAttemptsDefinedAndAttemptsExceeded() {

        try {
            // Attempt multiple logins and confirm that no lockout by default
            $this->authenticationService->login("mary@shoppingonline.com", "BADPASS");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
        }

        try {
            // Attempt multiple logins and confirm that no lockout by default
            $this->authenticationService->login("mary@shoppingonline.com", "BADPASS");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
        }

        try {
            // Attempt multiple logins and confirm that no lockout by default
            $this->authenticationService->login("mary@shoppingonline.com", "BADPASS");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
        }

        try {
            // Attempt multiple logins and confirm that no lockout by default
            $this->authenticationService->login("mary@shoppingonline.com", "BADPASS");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
        }

        try {
            // Attempt multiple logins and confirm that no lockout by default
            $this->authenticationService->login("mary@shoppingonline.com", "BADPASS");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
        }


        AuthenticationHelper::login("admin@kinicart.com", "password");

        $user = User::fetch(7);
        $this->assertEquals(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertEquals(0, $user->getInvalidLoginAttempts());


        // Add the config param and check lockout starts to happen
        Configuration::instance()->addParameter("login.max.attempts", 3);

        try {
            $this->authenticationService->login("mary@shoppingonline.com", "BADPASS");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
        }

        $user = User::fetch(7);
        $this->assertEquals(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertEquals(1, $user->getInvalidLoginAttempts());


        try {
            $this->authenticationService->login("mary@shoppingonline.com", "BADPASS");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
        }

        $user = User::fetch(7);
        $this->assertEquals(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertEquals(2, $user->getInvalidLoginAttempts());


        try {
            $this->authenticationService->login("mary@shoppingonline.com", "BADPASS");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
        }

        $user = User::fetch(7);
        $this->assertEquals(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertEquals(3, $user->getInvalidLoginAttempts());


        try {
            $this->authenticationService->login("mary@shoppingonline.com", "BADPASS");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
        }

        $user = User::fetch(7);
        $this->assertEquals(User::STATUS_LOCKED, $user->getStatus());
        $this->assertEquals(3, $user->getInvalidLoginAttempts());


        try {
            $this->authenticationService->login("mary@shoppingonline.com", "BADPASS");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
        }

        $user = User::fetch(7);
        $this->assertEquals(User::STATUS_LOCKED, $user->getStatus());
        $this->assertEquals(3, $user->getInvalidLoginAttempts());


        $pendingActions = $this->pendingActionService->getAllPendingActionsForTypeAndObjectId("USER_LOCKED", 7);
        $this->assertEquals(1, sizeof($pendingActions));
        $identifier = $pendingActions[0]->getIdentifier();

        // Check for an email containing the identifier
        $lastEmail = StoredEmail::filter("ORDER BY id DESC")[0];

        $this->assertEquals(["Mary Shopping <mary@shoppingonline.com>"], $lastEmail->getRecipients());
        $this->assertEquals("Your Kiniauth Example user account has been locked", $lastEmail->getSubject());
        $this->assertStringContainsString($identifier, $lastEmail->getTextBody());
        $this->assertStringContainsString("http://localhost:5013/sign-in/unlock", $lastEmail->getTextBody());


        // Check we can't log in legitimately as we are locked.
        try {
            AuthenticationHelper::login("mary@shoppingonline.com", "password");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
        }


        AuthenticationHelper::login("admin@kinicart.com", "password");

        $user->setStatus(User::STATUS_ACTIVE);
        $user->setInvalidLoginAttempts(0);
        $user->save();


    }


    public function testSuccessfulLoginsIsIncrementedIfLoginSucceeds() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $user = User::fetch(2);
        $successfulLogins = $user->getSuccessfulLogins();


        // Attempt a login.
        try {
            AuthenticationHelper::login("sam@samdavisdesign.co.uk", "badpass");
        } catch (\Exception $e) {
            // Fine
        }

        $user = User::fetch(2);
        $this->assertEquals($successfulLogins, $user->getSuccessfulLogins());

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $user = User::fetch(2);
        $this->assertEquals($successfulLogins + 1, $user->getSuccessfulLogins());

        $this->assertEquals($successfulLogins + 1, $this->session->__getLoggedInSecurable()->getSuccessfulLogins());


    }


    public function testExceptionRaisedIfInvalidUsernameOrPasswordSupplied() {


        AuthenticationHelper::logout();

        try {
            $this->authenticationService->login("bobby@wrong.test", "helloworld");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
            // Success
        }

        try {
            $this->authenticationService->login("admin@kinicart.com", "helloworld");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
            // Success
        }

        $this->assertTrue(true);
    }


    public function testExceptionRaisedIfUserIsPendingOrSuspended() {


        try {
            AuthenticationHelper::login("suspended@suspendeduser.com", "password");
            $this->fail("Should have thrown here");
        } catch (UserSuspendedException $e) {
            // Success
        }

        try {
            AuthenticationHelper::login("pending@myfactoryoutlet.com", "password");
            $this->fail("Should have thrown here");
        } catch (InvalidLoginException $e) {
            // Success
        }


        $this->assertTrue(true);

    }


    public function testIfAccountSuspendedUsersCannotLoginToThatAccount() {

        // Test one where the user is attached to a single account which is suspended.
        try {
            AuthenticationHelper::login("john@shoppingonline.com", "password");
            $this->fail("Should have thrown here");
        } catch (AccountSuspendedException $e) {
            // Success
        }


        // now test one with an active account which is suspended.  Check that the active account is set to the alternative account.
        AuthenticationHelper::login("mary@shoppingonline.com", "password");

        $loggedInUser = $this->session->__getLoggedInSecurable();
        $this->assertTrue($loggedInUser instanceof User);
        $this->assertEquals(7, $loggedInUser->getId());

        $loggedInAccount = $this->session->__getLoggedInAccount();
        $this->assertTrue($loggedInAccount instanceof AccountSummary);
        $this->assertEquals(2, $loggedInAccount->getAccountId());


    }


    public function testCanAuthenticateWithValidUserAccessTokens() {


        // Login as admin to read bob
        Container::instance()->get(ActiveRecordInterceptor::class)->executeInsecure(function () {


            $this->authenticationService->logout();

            try {
                $this->authenticationService->authenticateByUserToken("BADTOKEN");
                $this->fail("Should have thrown here");
            } catch (InvalidUserAccessTokenException $e) {
                // Success
            }


            // Try a simple token
            $this->authenticationService->authenticateByUserToken("TESTTOKEN");

            $loggedInUser = $this->session->__getLoggedInSecurable();
            $this->assertTrue($loggedInUser instanceof User);
            $this->assertEquals(4, $loggedInUser->getId());
            $this->assertEquals(AuthenticationHelper::hashNewPassword("TESTTOKEN"), $this->session->__getLoggedInUserAccessTokenHash());

            $this->authenticationService->logout();

            // Try a token with secondary token without secondary
            try {
                $this->authenticationService->authenticateByUserToken("TESTTOKEN2");
                $this->fail("Should have thrown here");
            } catch (InvalidUserAccessTokenException $e) {
                // Success
            }

            // Try a simple token
            $this->authenticationService->authenticateByUserToken("TESTTOKEN2", "TESTSECONDARY");

            $loggedInUser = $this->session->__getLoggedInSecurable();
            $this->assertTrue($loggedInUser instanceof User);
            $this->assertEquals(7, $loggedInUser->getId());

            $this->assertEquals(AuthenticationHelper::hashNewPassword("TESTTOKEN2--TESTSECONDARY"), $this->session->__getLoggedInUserAccessTokenHash());

        });

    }


    public function testAuthenticateByUserTokenIsOptimisedIfAlreadyLoggedInWithSameToken() {

        /**
         * @var $mockObjectProvider MockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);

        $securityService = $mockObjectProvider->getMockInstance(SecurityService::class);

        $authenticationService = new AuthenticationService(Container::instance()->get(SettingsService::class), $this->session, $securityService, null,
            Container::instance()->get(HashProvider::class),
            Container::instance()->get(EmailService::class), Container::instance()->get(PendingActionService::class),
            Container::instance()->get(UserSessionService::class));

        $this->session->__setLoggedInUserAccessTokenHash(AuthenticationHelper::hashNewPassword("TESTTOKEN"));

        $interceptor = Container::instance()->get(ActiveRecordInterceptor::class);

        $interceptor->executeInsecure(function () use ($authenticationService) {
            $authenticationService->authenticateByUserToken("TESTTOKEN");
        });


        $this->assertFalse($securityService->methodWasCalled("login"));

    }


    public function testCanAuthenticateWithAPICredentials() {


        // Login as admin to read bob
        Container::instance()->get(ActiveRecordInterceptor::class)->executeInsecure(function () {


            try {
                $this->authenticationService->apiAuthenticate("BADKEY", "BADSECRET");
                $this->fail("Should have thrown here");
            } catch (InvalidAPICredentialsException $e) {
                // Success
            }

            // Authenticate with an account level key
            $this->authenticationService->apiAuthenticate("GLOBALACCOUNTAPIKEY", "GLOBALACCOUNTAPISECRET");


            $this->assertEquals(APIKey::fetch(1), $this->session->__getLoggedInSecurable());
            $this->assertEquals(Account::fetch(2), $this->session->__getLoggedInAccount());


            // Authenticate with a project level key
            $this->authenticationService->apiAuthenticate("PROJECTSPECIFICKEY", "PROJECTSPECIFICSECRET");

            $this->assertEquals(APIKey::fetch(2), $this->session->__getLoggedInSecurable());
            $this->assertEquals(Account::fetch(2), $this->session->__getLoggedInAccount());

        });
    }


    public function testCanLogOut() {
        AuthenticationHelper::login("james@smartcoasting.org", "password");
        $this->assertNotNull($this->session->__getLoggedInAccount());
        $this->assertNotNull($this->session->__getLoggedInSecurable());

        $this->authenticationService->logout();
        $this->assertNull($this->session->__getLoggedInAccount());
        $this->assertNull($this->session->__getLoggedInSecurable());

    }

    public function testSessionReferrerAndParentAccountIsCorrectlyUpdatedWhenCallingUpdateParentAccountOrExceptionRaisedIfInvalidReferrer() {


        $url = new URL("https://www.google.com/hello/123");

        try {
            $this->authenticationService->updateActiveParentAccount($url);
            $this->fail("Should have thrown here");
        } catch (InvalidReferrerException $e) {
            // Yes
        }

        $this->authenticationService->updateActiveParentAccount(new URL("http://kinicart.example/mark"));

        $this->assertEquals("kinicart.example", $this->session->__getReferringURL());
        $this->assertEquals(0, $this->session->__getActiveParentAccountId());


        $this->authenticationService->updateActiveParentAccount(new URL("http://samdavis.org/mark"));

        $this->assertEquals("samdavis.org", $this->session->__getReferringURL());
        $this->assertEquals(1, $this->session->__getActiveParentAccountId());

    }

    public function testIfLoggedInAndParentAccountHasChangedOnUpdateUserUserLoggedOutForSecurity() {

        $this->authenticationService->updateActiveParentAccount(new URL("https://kinicart.test/hello/123"));

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $this->assertNotNull($this->session->__getLoggedInSecurable());
        $this->assertNotNull($this->session->__getLoggedInAccount());

        $this->authenticationService->updateActiveParentAccount(new URL("http://samdavis.org/mark"));

        $this->assertNull($this->session->__getLoggedInSecurable());
        $this->assertNull($this->session->__getLoggedInAccount());

    }


    public function testIfSingleSessionLoginSpecifiedActiveSessionStatusIsReturnedIfSessionExistsAndPendingObjectAddedToSession() {

        $this->authenticationService->logout();

        Configuration::instance()->addParameter("login.single.session", true);


        $activeRecordInterceptor = Container::instance()->get(ActiveRecordInterceptor::class);


        $activeRecordInterceptor->executeInsecure(function () {

            /**
             * @var MockObjectProvider $mockObjectProvider
             */
            $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);
            $mockUserSessionService = $mockObjectProvider->getMockInstance(UserSessionService::class);


            $mockUserSessionService->returnValue("listAuthenticatedSessions", [
                new UserSession(2, "ABCDEFG", new UserSessionProfile("1.1.1.1", "html/1.1", 2))], [2]);

            $authenticationService = new AuthenticationService(Container::instance()->get(SettingsService::class),
                $this->session,
                Container::instance()->get(SecurityService::class),
                $this->twoFactorProvider,
                Container::instance()->get(HashProvider::class),
                Container::instance()->get(UserService::class),
                $mockUserSessionService);

            $authenticationService->updateActiveParentAccount(new URL("http://kinicart.example/mark"));


            // Do a regular user

            $result = $authenticationService->login("sam@samdavisdesign.co.uk", AuthenticationHelper::encryptPasswordForLogin("passwordsam@samdavisdesign.co.uk"));
            $this->assertEquals(AuthenticationService::STATUS_ACTIVE_SESSION, $result);


            $pendingUser = $this->session->__getPendingLoggedInUser();
            $this->assertTrue($pendingUser instanceof User);
            $this->assertEquals(2, $pendingUser->getId());


            // Close active sessions for the pending user and continue with login.
            $result = $authenticationService->closeActiveSessionsAndLogin();

            $this->assertEquals(AuthenticationService::STATUS_LOGGED_IN, $result);
            $this->assertNull($this->session->__getPendingLoggedInUser());
            $this->assertEquals(2, $this->session->__getLoggedInSecurable()->getId());

            $this->assertTrue($mockUserSessionService->methodWasCalled("terminateAuthenticatedSession",
                [2, "ABCDEFG"]));


            $authenticationService->logout();

            // Now try a 2fa user
            $mockUserSessionService->returnValue("listAuthenticatedSessions", [
                new UserSession(11, "XXXYYY", new UserSessionProfile("1.1.1.1", "html/1.1", 11))], [11]);


            // Simulate 2FA
            $bob = User::fetch(11);
            $this->twoFactorProvider->returnValue("generateTwoFactorIfRequired", 98765, [
                $bob, 1234567
            ]);

            $result = $authenticationService->login("bob@twofactor.com", AuthenticationHelper::encryptPasswordForLogin("passwordbob@twofactor.com"), 1234567);
            $this->assertEquals(AuthenticationService::STATUS_ACTIVE_SESSION, $result);

            // Check pending user was saved in session
            $pendingUser = $this->session->__getPendingLoggedInUser();
            $this->assertTrue($pendingUser instanceof User);
            $this->assertEquals(11, $pendingUser->getId());

            // Check client data was saved in session as pending 2FA data
            $this->assertEquals(1234567, $this->session->__getPendingTwoFactorData());


            // Close active sessions for the pending user and continue with login.
            $result = $authenticationService->closeActiveSessionsAndLogin();

            $this->assertTrue($mockUserSessionService->methodWasCalled("terminateAuthenticatedSession",
                [11, "XXXYYY"]));


            // Check we still have pending user ready for 2fa
            $this->assertEquals(AuthenticationService::STATUS_REQUIRES_2FA, $result);
            $pendingUser = $this->session->__getPendingLoggedInUser();
            $this->assertTrue($pendingUser instanceof User);
            $this->assertEquals(11, $pendingUser->getId());
            $this->assertNull($this->session->__getLoggedInSecurable());

            // Check we have pending two factor data
            $this->assertEquals(98765, $this->session->__getPendingTwoFactorData());


        });


        Configuration::instance()->addParameter("login.single.session", false);

    }


    public function testExceptionRaisedIfAttemptToAuthenticateWithTwoFactorWithoutPendingUserInSession() {

        $this->session->__setPendingLoggedInUser(null);

        try {
            $this->authenticationService->authenticateTwoFactor(123456);
            $this->fail("Should have thrown exception here");
        } catch (InvalidLoginException $e) {
            $this->assertTrue(true);
        }
    }

    public function testCanAuthenticateTwoFactorUsingProviderAndPassedLoginDataIfPendingUser() {

        // Login as admin to read bob
        Container::instance()->get(ActiveRecordInterceptor::class)->executeInsecure(function () {

            $bob = User::fetch(11);
            $this->session->__setPendingLoggedInUser($bob);
            $this->session->__setPendingTwoFactorData(12345);

            $this->twoFactorProvider->returnValue("authenticate", false, [
                $bob, 12345, 76584
            ]);

            try {
                $this->authenticationService->authenticateTwoFactor(76584);
                $this->fail("Should have thrown here");
            } catch (InvalidLoginException $e) {
                $this->assertTrue(true);
                $this->assertEquals($bob, $this->session->__getPendingLoggedInUser());
                $this->assertEquals(12345, $this->session->__getPendingTwoFactorData());
                $this->assertNull($this->session->__getLoggedInSecurable());
            }

            $this->twoFactorProvider->returnValue("authenticate", 23456, [
                $bob, 12345, 76584
            ]);

            $this->assertEquals(23456, $this->authenticationService->authenticateTwoFactor(76584));

            $this->assertNull($this->session->__getPendingLoggedInUser());
            $this->assertNull($this->session->__getPendingTwoFactorData());
            $this->assertEquals($bob, $this->session->__getLoggedInSecurable());
        });


    }

}
