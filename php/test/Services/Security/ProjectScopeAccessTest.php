<?php


namespace Kiniauth\Test\Services\Security;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\ProjectSummary;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Account\ProjectService;
use Kiniauth\Services\Security\ProjectScopeAccess;
use Kiniauth\Test\TestBase;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;

include_once __DIR__ . "/../../autoloader.php";


class ProjectScopeAccessTest extends TestBase {


    /**
     * @var MockObject
     */
    private $projectService;

    /**
     * @var ProjectScopeAccess
     */
    private $projectScopeAccess;


    public function setUp(): void {
        $this->projectService = MockObjectProvider::instance()->getMockInstance(ProjectService::class);
        $this->projectScopeAccess = new ProjectScopeAccess($this->projectService);
    }


    public function testSuperAdminGetFullAccessToAllProjects() {

        // Superuser privileges
        $accountPrivileges = ["*" => ["*"]];

        $user = new User("superuser@test.com");

        $scopePrivileges = $this->projectScopeAccess->generateScopePrivileges($user, null, $accountPrivileges);

        $this->assertEquals(["*" => ["*"]], $scopePrivileges);

    }

    public function testAccountAdminGetFullAccessToAllProjects() {

        // Account admin
        $accountPrivileges = [1 => ["*"], 2 => ["access"]];

        $user = new User("accountadmin@test.com");

        $scopePrivileges = $this->projectScopeAccess->generateScopePrivileges($user, null, $accountPrivileges);

        $this->assertEquals(["*" => ["*"]], $scopePrivileges);

    }

    public function testIfAccountPassedButNoUserFullAccessIsGranted() {
        $scopePrivileges = $this->projectScopeAccess->generateScopePrivileges(null, new Account("Bingo"), []);
        $this->assertEquals(["*" => ["*"]], $scopePrivileges);
    }


    public function testIfExplicitRolesSuppliedForUserTheseAreEvaluatedToPrivilegesAndReturned() {

        $role1 = new Role(ProjectScopeAccess::SCOPE_PROJECT, "Access Project", "Access Project", "Access Project", [
            "access"
        ]);

        $role2 = new Role(ProjectScopeAccess::SCOPE_PROJECT, "Edit Project", "Edit Project", "Edit Project", [
            "access",
            "edit"
        ]);

        $userRole1 = new UserRole(ProjectScopeAccess::SCOPE_PROJECT, "myProject", 3);
        $userRole1->setRole($role1);

        $userRole2 = new UserRole(ProjectScopeAccess::SCOPE_PROJECT, "otherProject", 4);
        $userRole2->setRole($role2);


        $roles = [
            $userRole1,
            $userRole2
        ];

        $user = new User("regularuser@test.com");
        $user->setRoles($roles);

        $scopePrivileges = $this->projectScopeAccess->generateScopePrivileges($user, null, []);

        $this->assertEquals([
            "myProject" => [
                "access"
            ],
            "otherProject" => [
                "access",
                "edit"
            ]
        ], $scopePrivileges);

    }

    public function testCanGetScopeObjectDescriptionsById() {


        $this->projectService->returnValue("getMultipleProjects", [
            new ProjectSummary("Bingo"),
            new ProjectSummary("Bongo")
        ], [
            ["myBigOne",
                "myTestOne"], 5
        ]);

        $projectDescriptions = $this->projectScopeAccess->getScopeObjectDescriptionsById([
            "myBigOne",
            "myTestOne"
        ], 5);


        $this->assertEquals(["Bingo", "Bongo"], $projectDescriptions);

    }

    public function testCanGetFilteredScopeObjectDescriptions() {

        $this->projectService->returnValue("filterProjects", [
            new ProjectSummary("Bingo","", "myBigOne"),
            new ProjectSummary("Bongo", "", "myTestOne")
        ], [
            "filterstring",
                0, 10, 5
        ]);

        $projectDescriptions = $this->projectScopeAccess->getFilteredScopeObjectDescriptions("filterstring",0, 10, 5);


        $this->assertEquals(["myBigOne" => "Bingo", "myTestOne" => "Bongo"], $projectDescriptions);


    }


}