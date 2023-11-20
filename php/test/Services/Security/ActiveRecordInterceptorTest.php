<?php


namespace Kiniauth\Test\Services\Application;


use Kiniauth\Objects\Account\Contact;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\Services\Security\TestNonAccountObject;
use Kiniauth\Test\Services\Security\TestTimestampObject;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;

include_once __DIR__ . "/../../autoloader.php";

class ActiveRecordInterceptorTest extends TestBase {

    /**
     * @var \Kiniauth\Services\Application\ActiveRecordInterceptor
     */
    private $objectInterceptor;

    /**
     * @var \Kiniauth\Services\Application\AuthenticationService
     */
    private $authenticationService;

    public function setUp(): void {
        parent::setUp();
        $this->objectInterceptor = Container::instance()->get(ActiveRecordInterceptor::class);
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
    }


    public function testObjectsImplementingTheTimestampTraitAreAutomaticallyTimestampedWithCreateAndLastModifiedDatesOnPreSave() {

        /**
         * @var DatabaseConnection $databaseConnection
         */
        $databaseConnection = Container::instance()->get(DatabaseConnection::class);
        $databaseConnection->query("DROP TABLE IF EXISTS test_timestamp_object");
        $databaseConnection->query("CREATE TABLE test_timestamp_object (id INTEGER AUTO_INCREMENT, name VARCHAR, created_date DATETIME, last_modified_date DATETIME)");


        // try new one
        $object = new TestTimestampObject(null, "mark");
        $this->objectInterceptor->preSave($object);

        $this->assertEquals((new \DateTime())->format("Y-m-d H:i:s"), $object->getCreatedDate()->format("Y-m-d H:i:s"));
        $this->assertEquals((new \DateTime())->format("Y-m-d H:i:s"), $object->getLastModifiedDate()->format("Y-m-d H:i:s"));

        $databaseConnection->query("INSERT INTO test_timestamp_object VALUES(1, 'mark', '2020-01-01 10:00:00','2020-01-01 10:00:00') ");


        // Check creation date preserved on save
        $object = new TestTimestampObject(1, "mark", null,
            date_create_from_format("Y-m-d H:i:s", "2020-01-01 10:00:00"));

        $this->objectInterceptor->preSave($object);

        $this->assertEquals("2020-01-01 10:00:00", $object->getCreatedDate()->format("Y-m-d H:i:s"));
        $this->assertEquals((new \DateTime())->format("Y-m-d H:i:s"), $object->getLastModifiedDate()->format("Y-m-d H:i:s"));


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
