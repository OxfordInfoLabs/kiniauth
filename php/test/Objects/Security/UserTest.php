<?php


namespace Kiniauth\Test\Objects\Security;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\ValidationException;

include_once "autoloader.php";

class UserTest extends TestBase {
    private $authenticationService;
    private $accountService;

    public function setUp(): void {
        parent::setUp();
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->accountService = Container::instance()->get(AccountService::class);
    }



    public function testCannotUpdatePasswordWithPreviouslyUsedHash() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $user = new User("hello@myworld.com", hash("sha512", "newpassword1"), "Zebra World");
        $user->save();

        // Update to new password
        $user->setHashedPassword(hash("sha512", "newpassword2"));
        $user->save();

        try {
            $user->setHashedPassword(hash("sha512", "newpassword1"));
            $user->save();
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }


    }

    public function testCanGetInactiveAccountStatus(){

        $newUser = new User("sheyla@badgers.com", hash("sha512", "passwordsheyla@badgers.com"), "Silly Sheyla", 0, 1);
        $newAccount = new Account("Badgers inc", 0, Account::STATUS_ACTIVE, 1);
        $role = new UserRole(Role::SCOPE_ACCOUNT, $newAccount->getAccountId(), 0, 1,1 );
        $newUser->setRoles([$role]);

        // Check user does not have inactive account
        $this->assertEquals(null, $newUser->getInactiveAccountStatus());

        // Set account to expired
        $newAccount->setStatus(Account::STATUS_EXPIRED);
        $this->assertEquals(Account::STATUS_EXPIRED, $newUser->getInactiveAccountStatus());

        // Set account to suspended
        $newAccount->setStatus(Account::STATUS_SUSPENDED);
        $this->assertEquals(Account::STATUS_SUSPENDED, $newUser->getInactiveAccountStatus());

        // Create a new expired account
        $newAccount2 = new Account("Another Company", 0, Account::STATUS_EXPIRED, 2);
        $role2 = new UserRole(Role::SCOPE_ACCOUNT, $newAccount2->getAccountId(), 0, 2,1);
        $newUser->setRoles([$role, $role2]);

        // Check that we prioritise the EXPIRED status over the SUSPENDED status
        $this->assertEquals(Account::STATUS_EXPIRED, $newUser->getInactiveAccountStatus());

    }

}