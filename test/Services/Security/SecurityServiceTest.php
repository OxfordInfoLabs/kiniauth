<?php


namespace Kiniauth\Test\Services\Security;


use Kiniauth\Exception\Security\NonExistentPrivilegeException;
use Kiniauth\Objects\Account\Contact;
use Kiniauth\Objects\Security\Privilege;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;

include_once __DIR__ . "/../../autoloader.php";

class SecurityServiceTest extends TestBase {

    /**
     * @var SecurityService
     */
    private $securityService;
    private $authenticationService;

    public function setUp():void {
        parent::setUp();
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->securityService = Container::instance()->get(SecurityService::class);
    }


    public function testCanGetLoggedInAccountScopePrivileges() {

        // Logged out
        $this->authenticationService->logout();

        $this->assertEquals(array(), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 1));
        $this->assertEquals(array(), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 2));
        $this->assertEquals(array(), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 3));


        // Super user.
        $this->authenticationService->login("admin@kinicart.com", "password");

        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 1));
        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 2));


        // Account admin
        $this->authenticationService->login("sam@samdavisdesign.co.uk", "password");

        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 1));
        $this->assertEquals(array(), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 2));
        $this->assertEquals(array(), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 3));


        // User with dual account admin access.
        $this->authenticationService->login("mary@shoppingonline.com", "password");
        $this->assertEquals(array(), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 1));
        $this->assertEquals(array("viewdata", "editdata", "deletedata"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 2));
        $this->assertEquals(array(), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 3));
        $this->assertEquals(array("viewdata", "editdata"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 4));


        // User with sub accounts.
        $this->authenticationService->login("sam@samdavisdesign.co.uk", "password");
        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 5));


        // Account logged in by API
        $this->authenticationService->apiAuthenticate("TESTAPIKEY", "TESTAPISECRET");
        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 1));
        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 5));

    }

    public function testCanCheckObjectAccessWithAccountId() {

        $contact = new Contact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", 1, Contact::ADDRESS_TYPE_GENERAL);


        // Logged out
        $this->authenticationService->logout();
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // Super user
        $this->authenticationService->login("admin@kinicart.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));

        // User with different account access
        $this->authenticationService->login("mary@shoppingonline.com", "password");
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // API login
        $this->authenticationService->apiAuthenticate("TESTAPIKEY", "TESTAPISECRET");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));


    }

    public function testCanGetAllPrivileges() {

        $allPrivileges = $this->securityService->getAllPrivileges();

        $this->assertEquals(new Privilege("access", "Lowest level of access to an account.", "ACCOUNT"), $allPrivileges["access"]);
        $this->assertEquals(new Privilege("viewdata", "Test View Data Privilege.", "ACCOUNT"), $allPrivileges["viewdata"]);
        $this->assertEquals(new Privilege("editdata", "Test Edit Data Privilege.", "ACCOUNT"), $allPrivileges["editdata"]);
        $this->assertEquals(new Privilege("deletedata", "Test Delete Data Privilege.", "ACCOUNT"), $allPrivileges["deletedata"]);

    }


    public function testCanCheckLoggedInHasPrivilege() {

        // Try non-existent privilege first
        try {
            $this->securityService->checkLoggedInHasPrivilege("peterpan");
            $this->fail("Should have thrown here");
        } catch (NonExistentPrivilegeException $e) {
            // Success
        }


        // Logged out
        $this->authenticationService->logout();
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege("access"));
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege("access", 5));

        // Super user
        $this->authenticationService->login("admin@kinicart.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege("access"));
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege("viewdata"));
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege("editdata"));
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege("deletedata"));
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege("deletedata", 7));

        // Administrator
        $this->authenticationService->login("sam@samdavisdesign.co.uk", "password");
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege("access"));
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege("viewdata"));
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege("editdata"));
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege("access", 2));
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege("viewdata", 2));
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege("editdata", 2));

        // User with selective roles
        $this->authenticationService->login("regularuser@smartcoasting.org", "password");
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege("editdata"));
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege("deletedata"));
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege("editdata", 2));

    }

}
