<?php


namespace Kiniauth\Test\Services\Application;


use Kiniauth\Objects\Account\Contact;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\ObjectWorkflowService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\Services\Security\ExamplePropertyChangeObject;
use Kiniauth\Test\Services\Security\TestNonAccountObject;
use Kiniauth\Test\Services\Security\TestTimestampObject;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\ORM;

include_once __DIR__ . "/../../autoloader.php";

class ActiveRecordInterceptorTest extends TestBase {

    /**
     * @var ActiveRecordInterceptor
     */
    private $objectInterceptor;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var MockObject
     */
    private $orm;


    /**
     * @var MockObject
     */
    private $objectWorkflowService;


    public function setUp(): void {
        parent::setUp();

        $this->objectWorkflowService = MockObjectProvider::instance()->getMockInstance(ObjectWorkflowService::class);

        $this->orm = MockObjectProvider::instance()->getMockInstance(ORM::class);

        $this->objectInterceptor = new ActiveRecordInterceptor(Container::instance()->get(SecurityService::class),
            Container::instance()->get(Session::class), Container::instance()->get(ClassInspectorProvider::class),
            $this->orm, $this->objectWorkflowService);
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
    }


    public function testObjectsImplementingTheTimestampTraitAreAutomaticallyTimestampedWithCreateAndLastModifiedDatesOnPreSave() {


        $this->orm->returnValue("fetch", null, [TestTimestampObject::class, [null]]);


        // try new one
        $object = new TestTimestampObject(null, "mark");
        $this->objectInterceptor->preSave($object);

        $this->assertEquals((new \DateTime())->format("Y-m-d H:i:s"), $object->getCreatedDate()->format("Y-m-d H:i:s"));
        $this->assertEquals((new \DateTime())->format("Y-m-d H:i:s"), $object->getLastModifiedDate()->format("Y-m-d H:i:s"));


        $originalObject = new TestTimestampObject(1, "mark", date_create_from_format("Y-m-d H:i:s", "2020-01-01 10:00:00"),
            date_create_from_format("Y-m-d H:i:s", "2020-01-01 10:00:00"));

        // Check creation date preserved on save
        $object = new TestTimestampObject(1, "mark", null,
            date_create_from_format("Y-m-d H:i:s", "2020-01-01 10:00:00"));

        $this->orm->returnValue("fetch", $originalObject, [TestTimestampObject::class, [1]]);

        $this->objectInterceptor->preSave($object);

        $this->assertEquals("2020-01-01 10:00:00", $object->getCreatedDate()->format("Y-m-d H:i:s"));
        $this->assertEquals((new \DateTime())->format("Y-m-d H:i:s"), $object->getLastModifiedDate()->format("Y-m-d H:i:s"));


    }

    public function testObjectWorkflowServiceCalledForObjectsImplementingThePropertyChangeWorkflowInterfaceOnObjectPostSave() {


        $previousObject = new ExamplePropertyChangeObject(10, "Active");
        $newObject = new ExamplePropertyChangeObject(10, "Passive");

        // Programme a return value for the orm
        $this->orm->returnValue("fetch", $previousObject, [ExamplePropertyChangeObject::class, [10]]);

        $this->objectInterceptor->preSave($newObject);

        $this->orm->returnValue("fetch", $newObject, [ExamplePropertyChangeObject::class, [10]]);

        $this->objectInterceptor->postSave($newObject);

        // Check change workflow was called.
        $this->assertTrue($this->objectWorkflowService->methodWasCalled("processPropertyChangeWorkflowSteps", [
            ExamplePropertyChangeObject::class, 10, $previousObject, $newObject
        ]));

    }


    public function testAdhocObjectsNotContainingAccountIdAreAllowedThroughAllPreMethods() {

        $adhocObject = new TestNonAccountObject(1, "Marky Mark", "Marky Mark and the funky bunch");
        $this->assertTrue($this->objectInterceptor->preSave($adhocObject));
        $this->assertTrue($this->objectInterceptor->preDelete($adhocObject));
        $this->assertTrue($this->objectInterceptor->postMap($adhocObject));

    }


    public function testObjectsWithAccountIdAreCheckedForAccountOwnershipOfLoggedInUser() {


        $contact = new Contact("Mark", "Hello World", "1 This Lane", "This town", "London",
            "London", "LH1 4YY", "GB", null, null, 1);

        // Start logged out and confirm that interceptors fail.
        $this->authenticationService->logout();

        try {
            $this->objectInterceptor->preSave($contact);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        try {
            $this->objectInterceptor->preDelete($contact);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }


        $this->assertFalse($this->objectInterceptor->postMap($contact));


        // Now log in as a different account and confirm that interceptors fail.
        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        try {
            $this->objectInterceptor->preSave($contact);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        try {
            $this->objectInterceptor->preDelete($contact);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        $this->assertFalse($this->objectInterceptor->postMap($contact));


        // Now log in as an account with authority and confirm that interceptors succeed.
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $this->assertTrue($this->objectInterceptor->preSave($contact));
        $this->assertTrue($this->objectInterceptor->preDelete($contact));
        $this->assertTrue($this->objectInterceptor->postMap($contact));


    }


    public function testObjectsWithNullAccountIdAreAllowedForLoggedInReadButNotForWriteOrDelete() {

        $contact = new Contact("Mark", "Hello World", "1 This Lane", "This town", "London",
            "London", "LH1 4YY", "GB", null, null, null);


        // Start logged out and confirm that interceptors fail.
        $this->authenticationService->logout();

        try {
            $this->objectInterceptor->preSave($contact);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        try {
            $this->objectInterceptor->preDelete($contact);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }


        $this->assertFalse($this->objectInterceptor->postMap($contact));

        // Now log in as an account
        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        try {
            $this->objectInterceptor->preSave($contact);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        try {
            $this->objectInterceptor->preDelete($contact);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        // Should be able to read this one
        $this->assertTrue($this->objectInterceptor->postMap($contact));


    }


    public function testCanExecuteABlockInsecurelyWhichWillAlwaysReturnTrueForInterceptors() {

        $contact = new Contact("Mark", "Hello World", "1 This Lane", "This town", "London",
            "London", "LH1 4YY", "GB", null, null, 1);

        // Start logged out.
        $this->authenticationService->logout();

        // Check that the interceptor is disabled for the duration of this function
        $this->objectInterceptor->executeInsecure(function () use ($contact) {
            $this->assertTrue($this->objectInterceptor->preSave($contact));
            $this->assertTrue($this->objectInterceptor->preDelete($contact));
            $this->assertTrue($this->objectInterceptor->postMap($contact));
        });

        // And re-enabled afterwards.
        try {
            $this->objectInterceptor->preSave($contact);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        try {
            $this->objectInterceptor->preDelete($contact);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        $this->assertFalse($this->objectInterceptor->postMap($contact));


        try {

            // Check that an exception raised still resets the interceptor
            $this->objectInterceptor->executeInsecure(function () use ($contact) {
                throw new \Exception("Test Exception");
            });
        } catch (\Exception $e) {
            // Fine
        }

        // And re-enabled afterwards.
        try {
            $this->objectInterceptor->preSave($contact);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        try {
            $this->objectInterceptor->preDelete($contact);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        $this->assertFalse($this->objectInterceptor->postMap($contact));


    }


}
