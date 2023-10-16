<?php


namespace Kiniauth\Test\Services\Security;


use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Security\AccountScopeAccess;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;

include_once __DIR__ . "/../../autoloader.php";

class AccountScopeAccessTest extends TestBase {

    /**
     * @var AccountScopeAccess
     */
    private $accountScopeAccess;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    public function setUp():void {
        parent::setUp();
        $this->accountScopeAccess = new AccountScopeAccess();
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);

        AuthenticationHelper::login("admin@kinicart.com", "password");
    }


    public function testCanGenerateAccountScopePrivilegesFromUserRoles() {

        // Super user
        $user = User::fetch(1);
        $privileges = $this->accountScopeAccess->generateScopePrivileges($user, null, null);
        $this->assertEquals(array("*" => ["*"]), $privileges);


        // Account admin
        $user = User::fetch(2);
        $privileges = $this->accountScopeAccess->generateScopePrivileges($user, null, null);
        $this->assertEquals(["*"], $privileges[1]);

        // User with dual account admin access.
        $user = User::fetch(7);
        $privileges = $this->accountScopeAccess->generateScopePrivileges($user, null, null);
        $this->assertFalse(isset($privileges[1]));
        $this->assertEquals(["viewdata", "editdata", "deletedata"], $privileges[2]);
        $this->assertFalse(isset($privileges[3]));
        $this->assertEquals(["viewdata", "editdata"], $privileges[4]);

        // User with sub accounts.
        $user = User::fetch(2);
        $privileges = $this->accountScopeAccess->generateScopePrivileges($user, null, null);
        $this->assertEquals(["*"], $privileges[5]);


        // Account logged in by API
        $account = AccountSummary::fetch(1);
        $privileges = $this->accountScopeAccess->generateScopePrivileges(null, $account, null);
        $this->assertEquals(["*"], $privileges[1]);
        $this->assertEquals(["*"], $privileges[5]);


    }


}
