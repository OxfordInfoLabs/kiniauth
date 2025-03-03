<?php


namespace Kiniauth\Test\Services\Security;


use Kiniauth\Exception\Security\NonExistentPrivilegeException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Account\Contact;
use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\ObjectScopeAccess;
use Kiniauth\Objects\Security\Privilege;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Objects\Security\UserSummary;
use Kiniauth\Services\Application\Session;
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


    public function testCanCheckObjectAccessForUserObjects() {

        $user = new User("bob@test.com", "rtertwerwet", "Bob Test", 0, 2);
        $user->setRoles([new UserRole(Role::SCOPE_ACCOUNT, 1, 0, 1, 2)]);

        // Logged out
        $this->authenticationService->logout();
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($user));

        // Super user.
        AuthenticationHelper::login("admin@kinicart.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($user));

        // Logged in as target user
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($user));

        // Logged in as user with same account access
        AuthenticationHelper::login("bob@twofactor.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($user));

        // Logged in as user without same account access
        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($user));


        $userSummary = new UserSummary("Bob Brown", UserSummary::STATUS_ACTIVE, "bob@test.com", 0, [], 2);
        $userSummary->setRoles([new UserRole(Role::SCOPE_ACCOUNT, 1, 0, 1, 2)]);

        // Logged out
        $this->authenticationService->logout();
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($userSummary));

        // Super user.
        AuthenticationHelper::login("admin@kinicart.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($userSummary));

        // Logged in as target user
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($userSummary));

        // Logged in as user with same account access
        AuthenticationHelper::login("bob@twofactor.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($userSummary));

        // Logged in as user without same account access
        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($userSummary));

    }


    public function testCanCheckObjectAccessWithAccountId() {

        $contact = new Contact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", 2, Contact::ADDRESS_TYPE_GENERAL);

        $account = new Account("Mark Test", 0, Account::STATUS_ACTIVE, 2);

        $subAccountContact = new Contact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", 5, Contact::ADDRESS_TYPE_GENERAL);


        // Logged out
        $this->authenticationService->logout();
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // Super user
        AuthenticationHelper::login("admin@kinicart.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));

        // User with different account access fails
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // User with access to parent account succeeds.
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($subAccountContact));

        // User login where active account matches object account id should succeed.
        AuthenticationHelper::login("james@smartcoasting.org", "password");
        $session = Container::instance()->get(Session::class);
        $session->__setLoggedInAccount(new AccountSummary(2));
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));

        // User login where active account doesn't match object account id should fail.
        $session->__setLoggedInAccount(new AccountSummary(3));
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // Check that the user can access an object on another account where access non active scopes attribute has been set.
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($account));

        // API login
        $this->authenticationService->apiAuthenticate("GLOBALACCOUNTAPIKEY", "GLOBALACCOUNTAPISECRET");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));


    }

    public function testObjectsWhichHaveBeenSharedUsingObjectScopeAccessAreResolvedForSecurityCorrectlyForSimpleReadOperations() {

        $contact = new SharableContact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", 2, Contact::ADDRESS_TYPE_GENERAL, [
                new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 3, "group1"),
                new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 1, "group2")
            ]);


        // Logged out
        $this->authenticationService->logout();
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // Super user
        AuthenticationHelper::login("admin@kinicart.com", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));

        // User login where active account matches object account id should succeed.
        AuthenticationHelper::login("james@smartcoasting.org", "password");
        $session = Container::instance()->get(Session::class);
        $session->__setLoggedInAccount(new AccountSummary(2));
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));

        // User in account without permitted scope access
        AuthenticationHelper::login("peter@smartcoasting.org", "password");
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact));

        // User in account with permitted scope access
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact));

    }


    public function testObjectsWhichHaveBeenSharedUsingObjectScopeAccessAreResolvedForSecurityCorrectlyForWriteAndGrantOperations() {

        $contact = new SharableContact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", 2, Contact::ADDRESS_TYPE_GENERAL, [
                new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 3, "group1", true),
                new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 1, "group2", false, true)
            ]);


        // Write access granted but not grant access
        AuthenticationHelper::login("james@smartcoasting.org", "password");
        $session = Container::instance()->get(Session::class);
        $session->__setLoggedInAccount(new AccountSummary(3));
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_READ));
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_WRITE));
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_GRANT));

        // Grant access but not write access
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_READ));
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_WRITE));
        $this->assertTrue($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_GRANT));

    }


    public function testObjectsSharedWithExpiredScopeAccessesAreNotResolvedForSecurity() {

        $contact = new SharableContact("Mark R", "Test Organisation", "My Lane", "My Shire", "Oxford",
            "Oxon", "OX4 7YY", "GB", null, "test@test.com", 2, Contact::ADDRESS_TYPE_GENERAL, [
                new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 3, "group1", true, false, (new \DateTime())->sub(new \DateInterval("P1D"))),
                new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 1, "group2", false, true, (new \DateTime())->sub(new \DateInterval("P1D")))
            ]);


        // No access granted as expired
        AuthenticationHelper::login("james@smartcoasting.org", "password");
        $session = Container::instance()->get(Session::class);
        $session->__setLoggedInAccount(new AccountSummary(3));
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_READ));
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_WRITE));
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_GRANT));

        // No access granted as expired
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_READ));
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_WRITE));
        $this->assertFalse($this->securityService->checkLoggedInObjectAccess($contact, SecurityService::ACCESS_GRANT));

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

    public function testObjectsWithNullAccountIdAreAccessibleToSuperUsersInWriteAccessMode() {
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


    public function testCanBecomeSuperUser() {

        $this->securityService->logout();

        // Login as a machine super user
        $this->securityService->becomeSuperUser();

        // Should now be a super user
        $this->assertTrue($this->securityService->isSuperUserLoggedIn());

    }


    public function testCanBecomeUser() {

        $this->securityService->logout();

        $this->securityService->becomeSecurable("USER", 3);

        list($user, $account) = $this->securityService->getLoggedInSecurableAndAccount();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(3, $user->getId());

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals(2, $account->getAccountId());

        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 2));

    }


    public function testCanBecomeApiKey() {

        $this->securityService->logout();

        $this->securityService->becomeSecurable("API_KEY", 1);

        list($apiKey, $account) = $this->securityService->getLoggedInSecurableAndAccount();
        $this->assertInstanceOf(APIKey::class, $apiKey);
        $this->assertEquals(1, $apiKey->getId());


        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals(2, $account->getAccountId());

        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 2));


    }


    public function testCanBecomeAccount() {
        $this->securityService->logout();

        $this->securityService->becomeAccount(1);

        list($securable, $account) = $this->securityService->getLoggedInSecurableAndAccount();
        $this->assertNull($securable);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals(1, $account->getAccountId());

        $this->assertEquals(array("*"), $this->securityService->getLoggedInScopePrivileges(Role::SCOPE_ACCOUNT, 1));


    }


    public function testCanCheckObjectAccessScope() {

        // Simple object - no object scopes
        $test = new TestNonAccountObject(23, "Hello", "Test");

        // Simple non-sharable object with scope identified field
        $contact = new Contact("Me", "Test", "1 The Lane", null, null, null, "OX3 2WR", "GB", null, null, 2);

        // Sharable with extra scopes attached
        $sharableContact = new SharableContact("Me", "Test", "1 The Lane", null, null, null, "OX3 2WR", "GB", null, null, 2, null, [
            new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 3, "TEST"),
            new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 4, "TEST", true),
            new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 5, "TEST", false, true),
            new ObjectScopeAccess(Role::SCOPE_PROJECT, "HELLO", "TEST")
        ]);


        // None scoped objects should always be accessible to everything
        $this->assertTrue($this->securityService->checkObjectScopeAccess($test, Role::SCOPE_ACCOUNT, 2, SecurityService::ACCESS_READ));
        $this->assertTrue($this->securityService->checkObjectScopeAccess($test, Role::SCOPE_ACCOUNT, 2, SecurityService::ACCESS_WRITE));
        $this->assertTrue($this->securityService->checkObjectScopeAccess($test, Role::SCOPE_ACCOUNT, 2, SecurityService::ACCESS_GRANT));

        // Explicit scoped objects (column based) should be fully accesible to account holder only
        $this->assertTrue($this->securityService->checkObjectScopeAccess($contact, Role::SCOPE_ACCOUNT, 2, SecurityService::ACCESS_READ));
        $this->assertTrue($this->securityService->checkObjectScopeAccess($contact, Role::SCOPE_ACCOUNT, 2, SecurityService::ACCESS_WRITE));
        $this->assertTrue($this->securityService->checkObjectScopeAccess($contact, Role::SCOPE_ACCOUNT, 2, SecurityService::ACCESS_GRANT));

        $this->assertFalse($this->securityService->checkObjectScopeAccess($contact, Role::SCOPE_ACCOUNT, 3, SecurityService::ACCESS_READ));
        $this->assertFalse($this->securityService->checkObjectScopeAccess($contact, Role::SCOPE_ACCOUNT, 3, SecurityService::ACCESS_WRITE));
        $this->assertFalse($this->securityService->checkObjectScopeAccess($contact, Role::SCOPE_ACCOUNT, 3, SecurityService::ACCESS_GRANT));


        // Shared objects should be accessible via sharing according to rules
        $this->assertFalse($this->securityService->checkObjectScopeAccess($contact, Role::SCOPE_ACCOUNT, 1, SecurityService::ACCESS_READ));
        $this->assertFalse($this->securityService->checkObjectScopeAccess($contact, Role::SCOPE_ACCOUNT, 1, SecurityService::ACCESS_WRITE));
        $this->assertFalse($this->securityService->checkObjectScopeAccess($contact, Role::SCOPE_ACCOUNT, 1, SecurityService::ACCESS_GRANT));

        $this->assertTrue($this->securityService->checkObjectScopeAccess($sharableContact, Role::SCOPE_ACCOUNT, 2, SecurityService::ACCESS_READ));
        $this->assertTrue($this->securityService->checkObjectScopeAccess($sharableContact, Role::SCOPE_ACCOUNT, 2, SecurityService::ACCESS_WRITE));
        $this->assertTrue($this->securityService->checkObjectScopeAccess($sharableContact, Role::SCOPE_ACCOUNT, 2, SecurityService::ACCESS_GRANT));

        $this->assertTrue($this->securityService->checkObjectScopeAccess($sharableContact, Role::SCOPE_ACCOUNT, 3, SecurityService::ACCESS_READ));
        $this->assertFalse($this->securityService->checkObjectScopeAccess($sharableContact, Role::SCOPE_ACCOUNT, 3, SecurityService::ACCESS_WRITE));
        $this->assertFalse($this->securityService->checkObjectScopeAccess($sharableContact, Role::SCOPE_ACCOUNT, 3, SecurityService::ACCESS_GRANT));

        $this->assertTrue($this->securityService->checkObjectScopeAccess($sharableContact, Role::SCOPE_ACCOUNT, 4, SecurityService::ACCESS_READ));
        $this->assertTrue($this->securityService->checkObjectScopeAccess($sharableContact, Role::SCOPE_ACCOUNT, 4, SecurityService::ACCESS_WRITE));
        $this->assertFalse($this->securityService->checkObjectScopeAccess($sharableContact, Role::SCOPE_ACCOUNT, 4, SecurityService::ACCESS_GRANT));

        $this->assertTrue($this->securityService->checkObjectScopeAccess($sharableContact, Role::SCOPE_ACCOUNT, 5, SecurityService::ACCESS_READ));
        $this->assertFalse($this->securityService->checkObjectScopeAccess($sharableContact, Role::SCOPE_ACCOUNT, 5, SecurityService::ACCESS_WRITE));
        $this->assertTrue($this->securityService->checkObjectScopeAccess($sharableContact, Role::SCOPE_ACCOUNT, 5, SecurityService::ACCESS_GRANT));


    }


}
