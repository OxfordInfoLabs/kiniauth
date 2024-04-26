<?php

namespace Kiniauth\Test\Services\Security;

use Kiniauth\Exception\Security\NoObjectGrantAccessException;
use Kiniauth\Exception\Security\ObjectNotSharableException;
use Kiniauth\Objects\Application\Activity;
use Kiniauth\Objects\Security\ObjectScopeAccess;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\Security\ObjectScopeAccessService;
use Kiniauth\Services\Security\ScopeManager;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\TestBase;
use Kiniauth\Test\Traits\Security\TestSharable;
use Kiniauth\ValueObjects\Security\ScopeAccessGroup;
use Kiniauth\ValueObjects\Security\ScopeAccessItem;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\ORM;
use PHPUnit\Framework\MockObject\MockObject;

include_once "autoloader.php";

class ObjectScopeAccessServiceTest extends TestBase {

    // Service
    private ObjectScopeAccessService $service;

    private ORM $orm;
    private SecurityService $securityService;


    public function setUp(): void {
        $this->securityService = MockObjectProvider::instance()->getMockInstance(SecurityService::class);
        $this->orm = MockObjectProvider::instance()->getMockInstance(ORM::class);
        $this->service = new ObjectScopeAccessService($this->securityService, $this->orm, Container::instance()->get(ScopeManager::class));
    }


    /**
     * @doesNotPerformAssertions
     */
    public function testExceptionRaisedIfAttemptToAssignObjectScopeToObjectTypeWhichIsNotSharable() {

        try {
            $this->service->assignScopeAccessGroupsToObject(Activity::class, 1, [
                new ScopeAccessGroup([Role::SCOPE_ACCOUNT => 2])
            ]);
            $this->fail("Should have thrown here");
        } catch (ObjectNotSharableException $e) {
        }

    }


    /**
     * @return void
     * @throws NoObjectGrantAccessException
     * @throws ObjectNotSharableException
     *
     * @doesNotPerformAssertions
     */
    public function testLoggedInUserCheckedForGrantAccessBeforeProceedingWithShareOnAssignment() {


        $testSharable = new TestSharable(5, "Hello");

        // Programme return value for fetch
        $this->orm->returnValue("fetch", $testSharable, [TestSharable::class, 5]);

        // No grant access
        $this->securityService->returnValue("checkLoggedInObjectAccess", false, [$testSharable, SecurityService::ACCESS_GRANT]);

        try {
            $this->service->assignScopeAccessGroupsToObject(TestSharable::class, 5, [
                new ScopeAccessGroup([new ScopeAccessItem(Role::SCOPE_ACCOUNT, 2)])
            ]);
            $this->fail("Should have thrown here");
        } catch (NoObjectGrantAccessException $e) {
        }


        // With grant access
        $this->securityService->returnValue("checkLoggedInObjectAccess", true, [$testSharable, SecurityService::ACCESS_GRANT]);

        // Success
        $this->service->assignScopeAccessGroupsToObject(TestSharable::class, 5, [
            new ScopeAccessGroup([new ScopeAccessItem(Role::SCOPE_ACCOUNT, 2)])
        ]);


    }


    public function testAccessGroupItemsCorrectlySavedAsObjectScopeAccessObjectsForNewGroupOnAssignment() {

        $testSharable = new TestSharable(6, "Hello");

        // Programme return value for fetch
        $this->orm->returnValue("fetch", $testSharable, [TestSharable::class, 6]);

        // No grant access
        $this->securityService->returnValue("checkLoggedInObjectAccess", true, [$testSharable, SecurityService::ACCESS_GRANT]);

        $this->service->assignScopeAccessGroupsToObject(TestSharable::class, 6, [
            new ScopeAccessGroup([new ScopeAccessItem(Role::SCOPE_ACCOUNT, 2)]),
            new ScopeAccessGroup([new ScopeAccessItem(Role::SCOPE_ACCOUNT, 3), new ScopeAccessItem(Role::SCOPE_PROJECT, "testKey")], true, true, new \DateTime("2025-01-01 10:00:00"))
        ]);

        $matchingItems = ObjectScopeAccess::filter("WHERE shared_object_class_name = ? AND shared_object_primary_key = ? ORDER BY access_group, recipient_scope", TestSharable::class, 6);
        $this->assertEquals(3, sizeof($matchingItems));


        // Group names should be hashed.
        $test = hash("md5", "ACCOUNT:2");
        $test2 = hash("md5", "ACCOUNT:PROJECT:3:testKey");

        $this->assertEquals(new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 3, $test2, true, true, new \DateTime("2025-01-01 10:00:00"), TestSharable::class, 6), $matchingItems[0]);
        $this->assertEquals(new ObjectScopeAccess(Role::SCOPE_PROJECT, "testKey", $test2, true, true, new \DateTime("2025-01-01 10:00:00"), TestSharable::class, 6), $matchingItems[1]);
        $this->assertEquals(new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 2, $test, false, false, null, TestSharable::class, 6), $matchingItems[2]);

    }


    /**
     * @return void
     * @throws NoObjectGrantAccessException
     * @throws ObjectNotSharableException
     *
     * @doesNotPerformAssertions
     */
    public function testLoggedInUserCheckedForGrantAccessBeforeProceedingWithRemoveOfAssignment() {


        $testSharable = new TestSharable(5, "Hello");

        // Programme return value for fetch
        $this->orm->returnValue("fetch", $testSharable, [TestSharable::class, 5]);

        // No grant access
        $this->securityService->returnValue("checkLoggedInObjectAccess", false, [$testSharable, SecurityService::ACCESS_GRANT]);

        try {
            $this->service->removeScopeAccessGroupsFromObject(TestSharable::class, 5, ["test"]);
            $this->fail("Should have thrown here");
        } catch (NoObjectGrantAccessException $e) {
        }

    }


    public function testObjectScopeAccessesRemovedCorrectlyForAccessGroupIfAllowed() {

        $testSharable = new TestSharable(6, "Hello");

        // Programme return value for fetch
        $this->orm->returnValue("fetch", $testSharable, [TestSharable::class, 6]);

        // No grant access
        $this->securityService->returnValue("checkLoggedInObjectAccess", true, [$testSharable, SecurityService::ACCESS_GRANT]);

        $this->service->assignScopeAccessGroupsToObject(TestSharable::class, 6, [
            new ScopeAccessGroup([new ScopeAccessItem(Role::SCOPE_ACCOUNT, 2)]),
            new ScopeAccessGroup([new ScopeAccessItem(Role::SCOPE_ACCOUNT, 3), new ScopeAccessItem(Role::SCOPE_PROJECT, "testKey")], true, true, new \DateTime("2025-01-01 10:00:00"))
        ]);

        $matchingItems = ObjectScopeAccess::filter("WHERE shared_object_class_name = ? AND shared_object_primary_key = ? ORDER BY access_group, recipient_scope", TestSharable::class, 6);
        $this->assertEquals(3, sizeof($matchingItems));

        // Group names should be hashed.
        $test = hash("md5", "ACCOUNT:2");
        $test2 = hash("md5", "ACCOUNT:PROJECT:3:testKey");


        $this->service->removeScopeAccessGroupsFromObject(TestSharable::class, 6, [$test]);

        $matchingItems = ObjectScopeAccess::filter("WHERE shared_object_class_name = ? AND shared_object_primary_key = ? ORDER BY access_group, recipient_scope", TestSharable::class, 6);
        $this->assertEquals(2, sizeof($matchingItems));

        $this->service->removeScopeAccessGroupsFromObject(TestSharable::class, 6, [$test2]);

        $matchingItems = ObjectScopeAccess::filter("WHERE shared_object_class_name = ? AND shared_object_primary_key = ? ORDER BY access_group, recipient_scope", TestSharable::class, 6);
        $this->assertEquals(0, sizeof($matchingItems));

    }


    /**
     * @doesNotPerformAssertions
     */
    public function testExceptionRaisedIfAttemptToGetObjectScopeGroupsForObjectTypeWhichIsNotSharable() {

        try {
            $this->service->getScopeAccessGroupsForObject(Activity::class, 1);
            $this->fail("Should have thrown here");
        } catch (ObjectNotSharableException $e) {
        }

    }


    /**
     * @return void
     * @throws NoObjectGrantAccessException
     * @throws ObjectNotSharableException
     *
     * @doesNotPerformAssertions
     */
    public function testLoggedInUserCheckedForGrantAccessBeforeAllowingReturnOfAccessGroups() {


        $testSharable = new TestSharable(5, "Hello");

        // Programme return value for fetch
        $this->orm->returnValue("fetch", $testSharable, [TestSharable::class, 5]);

        // No grant access
        $this->securityService->returnValue("checkLoggedInObjectAccess", false, [$testSharable, SecurityService::ACCESS_GRANT]);

        try {
            $this->service->getScopeAccessGroupsForObject(TestSharable::class, 5);
            $this->fail("Should have thrown here");
        } catch (NoObjectGrantAccessException $e) {
        }

    }


    public function testAccessGroupsCorrectlyReturnedForObject() {

        $testSharable = new TestSharable(6, "Hello", [
            new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 3, "bingo"),
            new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 2, "bongo", true, true, new \DateTime("2025-01-01 10:00:00")),
            new ObjectScopeAccess(Role::SCOPE_PROJECT, "soapSuds", "bongo", true, true, new \DateTime("2025-01-01 10:00:00")),
            new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 4, "bango")]);

        // Programme return value for fetch
        $this->orm->returnValue("fetch", $testSharable, [TestSharable::class, 6]);

        // Programme grant access
        $this->securityService->returnValue("checkLoggedInObjectAccess", true, [$testSharable, SecurityService::ACCESS_GRANT]);

        // Log in as admin to clear interceptor
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $groups = $this->service->getScopeAccessGroupsForObject(TestSharable::class, 6);

        $this->assertEquals(3, sizeof($groups));
        $this->assertEquals(new ScopeAccessGroup([new ScopeAccessItem(Role::SCOPE_ACCOUNT, 3, "Smart Coasting", "Account")]), $groups[0]);
        $this->assertEquals(new ScopeAccessGroup([new ScopeAccessItem(Role::SCOPE_ACCOUNT, 2, "Peter Jones Car Washing", "Account"), new ScopeAccessItem(Role::SCOPE_PROJECT, "soapSuds", "", "Project")], true, true, new \DateTime("2025-01-01 10:00:00")), $groups[1]);
        $this->assertEquals(new ScopeAccessGroup([new ScopeAccessItem(Role::SCOPE_ACCOUNT, 4, "Suspended Account", "Account")]), $groups[2]);


    }


}