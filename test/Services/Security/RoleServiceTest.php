<?php


namespace Kiniauth\Test\Services\Security;


use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\RoleService;
use Kiniauth\Services\Security\ScopeManager;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\Security\AssignedRole;
use Kiniauth\ValueObjects\Security\ScopeRoles;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Validation\ValidationException;

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

        // Add example scope
        $this->scopeManager->addScopeAccess(new ExampleScopeAccess());

        $role1 = new Role("EXAMPLE", "Example Role 1", "Example Role 1", ["testpriv"]);
        $role2 = new Role("EXAMPLE", "Example Role 2", "Example Role 2", ["testpriv2"]);

        $role1->save();
        $role2->save();


        $allScopeRoles = $this->roleService->getAllPossibleAccountScopeRoles();
        $this->assertEquals(2, sizeof($allScopeRoles));

        $accountScopeRoles = $allScopeRoles[0];
        $this->assertTrue($accountScopeRoles instanceof ScopeRoles);
        $this->assertEquals("ACCOUNT", $accountScopeRoles->getScope());
        $this->assertEquals("Account", $accountScopeRoles->getScopeDescription());
        $this->assertEquals(3, sizeof($accountScopeRoles->getRoles()));
        $accountRoles = $accountScopeRoles->getRoles();
        $this->assertEquals("Viewer", $accountRoles[0]->getName());
        $this->assertEquals("Editor", $accountRoles[1]->getName());
        $this->assertEquals("Super Editor", $accountRoles[2]->getName());

        $exampleScopeRoles = $allScopeRoles[1];
        $this->assertTrue($exampleScopeRoles instanceof ScopeRoles);
        $this->assertEquals("EXAMPLE", $exampleScopeRoles->getScope());
        $this->assertEquals("Example", $exampleScopeRoles->getScopeDescription());
        $exampleRoles = $exampleScopeRoles->getRoles();
        $this->assertEquals(2, sizeof($exampleRoles));
        $this->assertEquals("Example Role 1", $exampleRoles[0]->getName());
        $this->assertEquals("Example Role 2", $exampleRoles[1]->getName());


    }


    public function testCanUpdateAssignedAccountRolesForUser() {

        $this->authenticationService->login("admin@kinicart.com", "password");

        $user = new User("crossaccountfun@test.com", "Password12345");
        $user->setRoles([
            new UserRole(Role::SCOPE_ACCOUNT, 1, 3, 1),
            new UserRole(Role::SCOPE_ACCOUNT, 2, 3, 2),
            new UserRole(Role::SCOPE_ACCOUNT, 3, 3, 3)
        ]);
        $user->setStatus(User::STATUS_ACTIVE);

        $user->save();


        // Log in as real user
        $this->authenticationService->login("sam@samdavisdesign.co.uk", "password");

        // Check we can't update users in different accounts
        try {

            $this->roleService->updateAssignedAccountRolesForUser(3, [
                new AssignedRole(1, 1),
                new AssignedRole(2, 1),
                new AssignedRole(3, 1)
            ]);

            $this->fail("Should have thrown here");

        } catch (AccessDeniedException $e) {
            $this->assertTrue(true);
        }


        // Update assigned account roles for user.
        $this->roleService->updateAssignedAccountRolesForUser($user->getId(), [
            new AssignedRole(1, 1),
            new AssignedRole(2, 1),
            new AssignedRole(3, 1)
        ]);


        $this->authenticationService->login("admin@kinicart.com", "password");

        $reUser = User::fetch($user->getId());
        $this->assertEquals(5, sizeof($reUser->getRoles()));


    }


    public function testValidationExceptionRaisedIfInvalidAssignedRolesPassed() {

        // Attempt to update account roles across accounts
        $this->authenticationService->login("sam@samdavisdesign.co.uk", "password");


        try {

            $this->roleService->updateAssignedAccountRolesForUser(10, [
                new AssignedRole(null, null)
            ]);

            $this->fail("Should have thrown here");

        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }


        try {

            $this->roleService->updateAssignedAccountRolesForUser(10, [
                new AssignedRole(1, null)
            ]);

            $this->fail("Should have thrown here");

        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }


    }


}
