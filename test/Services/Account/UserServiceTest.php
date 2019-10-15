<?php

namespace Kiniauth\Test\Services\Account;

use Kiniauth\Bootstrap;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\BootstrapService;
use Kiniauth\Services\Application\Session;
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
     * @var Session
     */
    private $session;


    public function setUp(): void {
        parent::setUp();
        $this->userService = Container::instance()->get(\Kiniauth\Services\Account\UserService::class);
        $this->authenticationService = Container::instance()->get(\Kiniauth\Services\Security\AuthenticationService::class);
        $this->session = Container::instance()->get(Session::class);
    }

    /**
     * Create a user with a brand new account.
     */
    public function testCanCreateUserWithABrandNewAccount() {

        $this->authenticationService->logout();

        // Simple one with just email address and password.
        $newUser = $this->userService->createWithAccount("john@test.com", "helloworld");

        $this->assertNotNull($newUser->getId());
        $this->assertEquals("john@test.com", $newUser->getEmailAddress());
        $this->assertEquals(hash("md5", "helloworld"), $newUser->getHashedPassword());
        $this->assertEquals(0, $newUser->getParentAccountId());
        $this->assertEquals(User::STATUS_PENDING, $newUser->getStatus());

        $this->assertEquals(1, sizeof($newUser->getRoles()));


        $this->assertEquals($newUser->getActiveAccountId(), $newUser->getRoles()[0]->getScopeId());
        $this->assertNull($newUser->getRoles()[0]->getRoleId());


        // Now do one with a users name, check propagation to account name.
        // Simple one with just email address and password.
        $newUser = $this->userService->createWithAccount("john2@test.com", "helloworld", "John Smith");

        $this->assertNotNull($newUser->getId());
        $this->assertEquals("john2@test.com", $newUser->getEmailAddress());
        $this->assertEquals("John Smith", $newUser->getName());
        $this->assertEquals(hash("md5", "helloworld"), $newUser->getHashedPassword());
        $this->assertEquals(0, $newUser->getParentAccountId());
        $this->assertEquals(User::STATUS_PENDING, $newUser->getStatus());

        $this->assertEquals(1, sizeof($newUser->getRoles()));


        // Now do one with a user and account name, check propagation to account name.
        // Simple one with just email address and password.
        $newUser = $this->userService->createWithAccount("john3@test.com", "helloworld", "John Smith",
            "Smith Enterprises");

        $this->assertNotNull($newUser->getId());
        $this->assertEquals("john3@test.com", $newUser->getEmailAddress());
        $this->assertEquals("John Smith", $newUser->getName());
        $this->assertEquals(hash("md5", "helloworld"), $newUser->getHashedPassword());
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
        $newUser = $this->userService->createWithAccount("john3@test.com", "helloworld", "John Smith",
            "Smith Enterprises", 1);

        $this->assertNotNull($newUser->getId());
        $this->assertEquals("john3@test.com", $newUser->getEmailAddress());
        $this->assertEquals("John Smith", $newUser->getName());
        $this->assertEquals(hash("md5", "helloworld"), $newUser->getHashedPassword());
        $this->assertEquals(1, $newUser->getParentAccountId());
        $this->assertEquals(User::STATUS_PENDING, $newUser->getStatus());

        $this->assertEquals(1, sizeof($newUser->getRoles()));


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
        $adminUser = $this->userService->createAdminUser("marko@polo.com", "pickle");

        $this->assertNotNull($adminUser->getId());
        $this->assertEquals("marko@polo.com", $adminUser->getEmailAddress());
        $this->assertEquals(hash("md5", "pickle"), $adminUser->getHashedPassword());
        $this->assertEquals(1, sizeof($adminUser->getRoles()));
        $this->assertEquals(0, $adminUser->getRoles()[0]->getScopeId());
        $this->assertNull($adminUser->getRoles()[0]->getRoleId());


        // Username, password and name one.
        $adminUser = $this->userService->createAdminUser("marko2@polo.com", "pickle", "Marko Polo");

        $this->assertNotNull($adminUser->getId());
        $this->assertEquals("marko2@polo.com", $adminUser->getEmailAddress());
        $this->assertEquals("Marko Polo", $adminUser->getName());
        $this->assertEquals(hash("md5", "pickle"), $adminUser->getHashedPassword());
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

}
