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

    public function setUp(): void {
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
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 1));
        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 2));


        // Account admin
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 1));
        $this->assertEquals(array(), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 2));
        $this->assertEquals(array(), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 3));


        // User with dual account admin access.
        AuthenticationHelper::login("mary@shoppingonline.com", "password");
        $this->assertEquals(array(), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 1));
        $this->assertEquals(array("viewdata", "editdata", "deletedata"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 2));
        $this->assertEquals(array(), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 3));
        $this->assertEquals(array("viewdata", "editdata"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 4));


        // User with sub accounts.
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 5));


        // Account logged in by API
        $this->authenticationService->apiAuthenticate("GLOBALACCOUNTAPIKEY", "GLOBALACCOUNTAPISECRET");
        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 2));

    }

    public function testCanCheckObjectAccessWithAccountId() {

        $contact = new Contact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", 2, Contact::ADDRESS_TYPE_GENERAL);


        // Logged out
        $this->authenticationService->logout();
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // Super user
        AuthenticationHelper::login("admin@kinicart.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));


        // User with different account access
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // Useer login
        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));

        // API login
        $this->authenticationService->apiAuthenticate("GLOBALACCOUNTAPIKEY", "GLOBALACCOUNTAPISECRET");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));


    }


    public function testObjectsWithAccountIdOfZeroAreSuperUserAccessibleOnly() {

        $contact = new Contact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", 0, Contact::ADDRESS_TYPE_GENERAL);


        // Logged out
        $this->authenticationService->logout();
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // Super user
        AuthenticationHelper::login("admin@kinicart.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));

        // User login
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // API login
        $this->authenticationService->apiAuthenticate("GLOBALACCOUNTAPIKEY", "GLOBALACCOUNTAPISECRET");
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));


    }


    public function testObjectsWithNullOrNegativeOneAccountIdAreAccessibleToAllLoggedInUsersInReadAccessMode() {


        // NULL ACCOUNT ID
        $contact = new Contact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", null, Contact::ADDRESS_TYPE_GENERAL);


        // Logged out
        $this->authenticationService->logout();
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // Super user
        AuthenticationHelper::login("admin@kinicart.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));

        // User login
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));

        // API login
        $this->authenticationService->apiAuthenticate("GLOBALACCOUNTAPIKEY", "GLOBALACCOUNTAPISECRET");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));


        // -1 Account Id
        $contact = new Contact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", -1, Contact::ADDRESS_TYPE_GENERAL);


        // Logged out
        $this->authenticationService->logout();
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // Super user
        AuthenticationHelper::login("admin@kinicart.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));

        // User login
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));

        // API login
        $this->authenticationService->apiAuthenticate("GLOBALACCOUNTAPIKEY", "GLOBALACCOUNTAPISECRET");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));


    }

    public function testObjectsWithNullAccountIdAreOnlyAccessibleToSuperUsersInWriteAccessMode() {
        $contact = new Contact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", null, Contact::ADDRESS_TYPE_GENERAL);


        // Logged out
        $this->authenticationService->logout();
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_WRITE));

        // Super user
        AuthenticationHelper::login("admin@kinicart.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_WRITE));

        // User login
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_WRITE));

        // API login
        $this->authenticationService->apiAuthenticate("GLOBALACCOUNTAPIKEY", "GLOBALACCOUNTAPISECRET");
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_WRITE));
    }


    public function testCanGetAllPrivileges() {

        $allPrivileges = $this->securityService->getAllPrivileges();

        $this->assertEquals(new Privilege("access", "Basic access to an account.", "ACCOUNT"), $allPrivileges["ACCOUNT"]["access"]);
        $this->assertEquals(new Privilege("viewdata", "Test View Data Privilege.", "ACCOUNT"), $allPrivileges["ACCOUNT"]["viewdata"]);
        $this->assertEquals(new Privilege("editdata", "Test Edit Data Privilege.", "ACCOUNT"), $allPrivileges["ACCOUNT"]["editdata"]);
        $this->assertEquals(new Privilege("deletedata", "Test Delete Data Privilege.", "ACCOUNT"), $allPrivileges["ACCOUNT"]["deletedata"]);

    }


    public function testCanCheckLoggedInHasPrivilege() {

        // Try non-existent privilege first
        try {
            $this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "peterpan");
            $this->fail("Should have thrown here");
        } catch (NonExistentPrivilegeException $e) {
            // Success
        }


        // Logged out
        $this->authenticationService->logout();
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "access"));
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "access", 5));

        // Super user
        AuthenticationHelper::login("admin@kinicart.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "access"));
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "viewdata"));
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "editdata"));
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "deletedata"));
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "deletedata", 7));

        // Administrator
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "access"));
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "viewdata"));
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "editdata"));
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "access", 2));
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "viewdata", 2));
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "editdata", 2));

        // User with selective roles
        AuthenticationHelper::login("regularuser@smartcoasting.org", "password");
        $this->assertTrue($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "editdata"));
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "deletedata"));
        $this->assertFalse($this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_ACCOUNT, "editdata", 1));

    }


    public function testCanLoginAsSuperUser(){

        $this->securityService->logout();

        // Login as a machine super user
        $this->securityService->loginAsSuperUser();

        // Should now be a super user
        $this->assertTrue($this->securityService->isSuperUserLoggedIn());

    }


}
