<?php


namespace Kiniauth\Test\Services\Security;


use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Objects\Security\APIKeyRole;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\RoleService;
use Kiniauth\Services\Security\ScopeManager;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Security\AssignedRole;
use Kiniauth\ValueObjects\Security\ScopeObjectRolesAssignment;
use Kiniauth\ValueObjects\Security\ScopeRoles;
use Kiniauth\ValueObjects\Security\ScopeObjectRoles;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;

include_once "autoloader.php";

class RoleServiceTest extends TestBase {

    /**
     * @var RoleService
     */
    private $roleService;

    /**
     * @var ScopeManager
     */
    private $scopeManager;


    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    protected function setUp(): void {
        $this->roleService = Container::instance()->get(RoleService::class);
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->scopeManager = Container::instance()->get(ScopeManager::class);
    }


    public function testCanGetAllPossibleAccountScopeRoles() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Add example scope
        $this->scopeManager->addScopeAccess(new ExampleScopeAccess());

        $role1 = new Role("EXAMPLE", Role::APPLIES_TO_ALL, "Example Role 1", "Example Role 1", ["testpriv"]);
        $role2 = new Role("EXAMPLE", Role::APPLIES_TO_USER, "Example Role 2", "Example Role 2", ["testpriv2"]);

        $role1->save();
        $role2->save();

        // Check applies to user
        $allScopeRoles = $this->roleService->getAllPossibleAccountScopeRoles(Role::APPLIES_TO_USER, 1);
        $this->assertEquals(3, sizeof($allScopeRoles));

        $accountScopeRoles = $allScopeRoles[0];
        $this->assertTrue($accountScopeRoles instanceof ScopeRoles);
        $this->assertEquals("ACCOUNT", $accountScopeRoles->getScope());
        $this->assertEquals("Account", $accountScopeRoles->getScopeDescription());

        $this->assertEquals(3, sizeof($accountScopeRoles->getRoles()));
        $accountRoles = $accountScopeRoles->getRoles();
        $this->assertEquals("Viewer", $accountRoles[0]->getName());
        $this->assertEquals("Editor", $accountRoles[1]->getName());
        $this->assertEquals("Super Editor", $accountRoles[2]->getName());

        $exampleScopeRoles = $allScopeRoles[2];
        $this->assertTrue($exampleScopeRoles instanceof ScopeRoles);
        $this->assertEquals("EXAMPLE", $exampleScopeRoles->getScope());
        $this->assertEquals("Example", $exampleScopeRoles->getScopeDescription());
        $exampleRoles = $exampleScopeRoles->getRoles();
        $this->assertEquals(2, sizeof($exampleRoles));
        $this->assertEquals("Example Role 1", $exampleRoles[0]->getName());
        $this->assertEquals("Example Role 2", $exampleRoles[1]->getName());

        // Check applies to api key
        $allScopeRoles = $this->roleService->getAllPossibleAccountScopeRoles(Role::APPLIES_TO_API_KEY, 1);
        $this->assertEquals(3, sizeof($allScopeRoles));

        $accountScopeRoles = $allScopeRoles[0];
        $this->assertTrue($accountScopeRoles instanceof ScopeRoles);
        $this->assertEquals("ACCOUNT", $accountScopeRoles->getScope());
        $this->assertEquals("Account", $accountScopeRoles->getScopeDescription());

        $this->assertEquals(1, sizeof($accountScopeRoles->getRoles()));
        $accountRoles = $accountScopeRoles->getRoles();
        $this->assertEquals("Viewer", $accountRoles[0]->getName());

        $exampleScopeRoles = $allScopeRoles[2];
        $this->assertTrue($exampleScopeRoles instanceof ScopeRoles);
        $this->assertEquals("EXAMPLE", $exampleScopeRoles->getScope());
        $this->assertEquals("Example", $exampleScopeRoles->getScopeDescription());
        $exampleRoles = $exampleScopeRoles->getRoles();
        $this->assertEquals(1, sizeof($exampleRoles));
        $this->assertEquals("Example Role 1", $exampleRoles[0]->getName());


    }


    public function testCanGetAllUserAccountRoles() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        // User case first

        $userRole1 = new UserRole("EXAMPLE", 1, 6, 1, 2);
        $userRole2 = new UserRole("EXAMPLE", 2, 7, 1, 2);
        $userRole3 = new UserRole("EXAMPLE", 1, 6, 2, 3);

        $userRole1->save();
        $userRole2->save();
        $userRole3->save();

        $allUserRoles = $this->roleService->getAllAccountRoles(Role::APPLIES_TO_USER, 2, 1);

        $this->assertEquals(3, sizeof($allUserRoles));
        $accountUserRoles = $allUserRoles["Account"];
        $exampleUserRoles = $allUserRoles["Example"];

        $this->assertEquals(1, sizeof($accountUserRoles));
        $this->assertEquals(new ScopeObjectRoles("ACCOUNT", 1, "Sam Davis Design", [
            null
        ]), $accountUserRoles[0]);


        $this->assertEquals(2, sizeof($exampleUserRoles));
        $this->assertEquals(new ScopeObjectRoles("EXAMPLE", 1, "EXAMPLE 1", [
            new Role("EXAMPLE", "ALL", "Example Role 1", "Example Role 1", ["testpriv"], 6),
        ]), $exampleUserRoles[0]);


        // API Key case next

        $apiRole2 = new APIKeyRole("EXAMPLE", 2, 6, 2, 1);
        $apiRole2->save();

        $allAPIRoles = $this->roleService->getAllAccountRoles(Role::APPLIES_TO_API_KEY, 1, 2);

        $this->assertEquals(3, sizeof($allAPIRoles));
        $accountAPIRoles = $allAPIRoles["Account"];
        $exampleAPIRoles = $allAPIRoles["Example"];

        $this->assertEquals(1, sizeof($accountAPIRoles));
        $this->assertEquals(new ScopeObjectRoles("ACCOUNT", 2, "Peter Jones Car Washing", [
            null
        ]), $accountAPIRoles[0]);


        $this->assertEquals(1, sizeof($exampleAPIRoles));
        $this->assertEquals(new ScopeObjectRoles("EXAMPLE", 2, "EXAMPLE 2", [
            new Role("EXAMPLE", "ALL", "Example Role 1", "Example Role 1", ["testpriv"], 6),
        ]), $exampleAPIRoles[0]);


    }


    public function testCanGetFilteredAssignableAccountScopeRolesForUsersAndAPICallsAndAppropriateCallsAreMade() {

        // Log in as real user
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $scopeRoles = $this->roleService->getFilteredAssignableAccountScopeRoles(Role::APPLIES_TO_USER, 2, "ACCOUNT");

        $this->assertEquals(1, sizeof($scopeRoles));
        $this->assertEquals(new ScopeObjectRoles("ACCOUNT", 1, "Sam Davis Design",
            [1 => Role::fetch(1),
                2 => Role::fetch(2),
                3 => Role::fetch(3)]), $scopeRoles[0]);


        $scopeRoles = $this->roleService->getFilteredAssignableAccountScopeRoles(Role::APPLIES_TO_USER, 2, "EXAMPLE");
        $this->assertEquals(5, sizeof($scopeRoles));
        $this->assertEquals(new ScopeObjectRoles("EXAMPLE", 1, "EXAMPLE 1",
            [
                6 => null,
                7 => Role::fetch(7),
            ]), $scopeRoles[0]);

        $this->assertEquals(new ScopeObjectRoles("EXAMPLE", 2, "EXAMPLE 2",
            [
                6 => null,
                7 => Role::fetch(7),
            ]), $scopeRoles[1]);

        $this->assertEquals(new ScopeObjectRoles("EXAMPLE", 3, "EXAMPLE 3",
            [
                6 => null,
                7 => Role::fetch(7),
            ]), $scopeRoles[2]);

        $this->assertEquals(new ScopeObjectRoles("EXAMPLE", 4, "EXAMPLE 4",
            [
                6 => null,
                7 => Role::fetch(7),
            ]), $scopeRoles[3]);

        $this->assertEquals(new ScopeObjectRoles("EXAMPLE", 5, "EXAMPLE 5",
            [
                6 => null,
                7 => Role::fetch(7),
            ]), $scopeRoles[4]);


        // NOW TRY API KEY ASSIGNABLE ROLES


        $scopeRoles = $this->roleService->getFilteredAssignableAccountScopeRoles(Role::APPLIES_TO_API_KEY, 1, "ACCOUNT");

        $this->assertEquals(1, sizeof($scopeRoles));
        $this->assertEquals(new ScopeObjectRoles("ACCOUNT", 1, "Sam Davis Design",
            [1 => Role::fetch(1)]), $scopeRoles[0]);


        $scopeRoles = $this->roleService->getFilteredAssignableAccountScopeRoles(Role::APPLIES_TO_API_KEY, 1, "EXAMPLE");
        $this->assertEquals(5, sizeof($scopeRoles));
        $this->assertEquals(new ScopeObjectRoles("EXAMPLE", 1, "EXAMPLE 1",
            [
                6 => Role::fetch(6)
            ]), $scopeRoles[0]);

        $this->assertEquals(new ScopeObjectRoles("EXAMPLE", 2, "EXAMPLE 2",
            [
                6 => Role::fetch(6)
            ]), $scopeRoles[1]);

        $this->assertEquals(new ScopeObjectRoles("EXAMPLE", 3, "EXAMPLE 3",
            [
                6 => Role::fetch(6)
            ]), $scopeRoles[2]);

        $this->assertEquals(new ScopeObjectRoles("EXAMPLE", 4, "EXAMPLE 4",
            [
                6 => Role::fetch(6)
            ]), $scopeRoles[3]);

        $this->assertEquals(new ScopeObjectRoles("EXAMPLE", 5, "EXAMPLE 5",
            [
                6 => Role::fetch(6)
            ]), $scopeRoles[4]);


    }


    public function testCanUpdateAssignedScopeObjectRoles() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        // USER CASE FIRST


        $user = new User("crossaccount@test.com", AuthenticationHelper::hashNewPassword("Password12345"));
        $user->setRoles([
            new UserRole(Role::SCOPE_ACCOUNT, 1, 3, 1),
            new UserRole(Role::SCOPE_ACCOUNT, 2, 3, 2),
            new UserRole(Role::SCOPE_ACCOUNT, 3, 3, 3),
            new UserRole("EXAMPLE", 1, 4, 1),
            new UserRole("EXAMPLE", 2, 5, 2),

        ]);
        $user->setStatus(User::STATUS_ACTIVE);

        $user->save();


        // Log in as real user
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");


        $scopeObjectRoles = [
            new  ScopeObjectRolesAssignment(Role::SCOPE_ACCOUNT, 1, [1, 2])
        ];

        $this->roleService->updateAssignedScopeObjectRoles(Role::APPLIES_TO_USER, $user->getId(), $scopeObjectRoles);


        // Now recheck the roles have been updated
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $userRoles = UserRole::filter("WHERE user_id = ?", $user->getId());
        $this->assertEquals(6, sizeof($userRoles));


        // API CASE NEXT

        $apiKey = new APIKey("Test one", [
            new APIKeyRole(Role::SCOPE_ACCOUNT, 1, 1, 1),
            new APIKeyRole("EXAMPLE", 1, 6, 1)
        ]);
        $apiKey->save();

        $scopeObjectRoles = [
            new ScopeObjectRolesAssignment("EXAMPLE", 2, [6], 1)
        ];

        $this->roleService->updateAssignedScopeObjectRoles(Role::APPLIES_TO_API_KEY, $apiKey->getId(), $scopeObjectRoles);

        $apiKeyRoles = APIKeyRole::filter("WHERE api_key_id = ?", $apiKey->getId());
        $this->assertEquals(3, sizeof($apiKeyRoles));

    }


}
