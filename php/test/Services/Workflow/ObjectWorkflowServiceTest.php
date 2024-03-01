<?php

namespace Kiniauth\Test\Services\Workflow;

use Kiniauth\Exception\Workflow\NoObjectWorkflowStepTaskImplementationException;
use Kiniauth\Exception\Workflow\ObjectWorkflowStepNotFoundException;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Workflow\ObjectWorkflowCompletedStep;
use Kiniauth\Objects\Workflow\ObjectWorkflowStep;
use Kiniauth\Services\Workflow\ObjectWorkflowService;
use Kiniauth\Services\Workflow\Task\Task;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\ORM\Mapping\ORMMapping;

include_once "autoloader.php";

/**
 * Test cases for the object workflow service
 */
class ObjectWorkflowServiceTest extends TestBase {

    /**
     * @var ObjectWorkflowService
     */
    private $service;

    /**
     * @var MockObject
     */
    private $task;


    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;

    public function setUp(): void {
        parent::setUp();
        $this->service = Container::instance()->get(ObjectWorkflowService::class);

        $this->task = MockObjectProvider::instance()->getMockInstance(Task::class);
        Container::instance()->set(get_class($this->task), $this->task);
        Container::instance()->addInterfaceImplementation(Task::class, "test-workflow", get_class($this->task));


        $this->databaseConnection = ORMMapping::get(Account::class)->getReadTableMapping()->getDatabaseConnection();

    }


    /**
     * @doesNotPerformAssertions
     */
    public function testWorkflowStepNotFoundExceptionRaisedIfInvalidStepKeyPassedForObjectClass() {

        try {
            $this->service->processWorkflowStep(Account::class, 11, "imaginary");
            $this->fail("Should have thrown here");
        } catch (ObjectWorkflowStepNotFoundException $e) {
        }


    }


    /**
     * @doesNotPerformAssertions
     */
    public function testNoWorkflowStepTaskImplementationExceptionRaisedIfInvalidTaskIdentifierSuppliedForWorkflowStep() {

        try {
            $this->service->processWorkflowStep(Account::class, 11, "invalid");
            $this->fail("Should have thrown here");
        } catch (NoObjectWorkflowStepTaskImplementationException $e) {
        }


    }


    public function testCanProcessSuccessfulWorkflowStepForValidObjectTypePkAndStep() {


        // Program return value
        $this->task->returnValue("run", "Sample Output", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "manualCheck"]), "objectPk" => 12]]);

        // process a manual step
        $this->service->processWorkflowStep(Account::class, 12, "manualCheck");

        $this->assertTrue($this->task->methodWasCalled("run", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "manualCheck"]), "objectPk" => 12]]));

        // Check we can get an entry for completed steps.
        $completedStep = ObjectWorkflowCompletedStep::fetch([Account::class, 12, "manualCheck", "N/A"]);
        $this->assertEquals(date("Y-m-d H:i:s"), $completedStep->getCompletedTime()->format("Y-m-d H:i:s"));
        $this->assertEquals(ObjectWorkflowCompletedStep::STATUS_COMPLETED, $completedStep->getStatus());
        $this->assertEquals("Sample Output", $completedStep->getLogOutput());
        $this->assertEquals("N/A", $completedStep->getTriggerValue());

    }


    public function testFailedWorkflowStepRecordedIfExceptionRaisedForWorkflowTask() {

        $this->task->throwException("run", new \Exception("Bad Action"), [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "manualCheck"]), "objectPk" => 13]]);

        // process a manual step
        $this->service->processWorkflowStep(Account::class, 13, "manualCheck");

        $this->assertTrue($this->task->methodWasCalled("run", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "manualCheck"]), "objectPk" => 13]]));

        // Check we can get an entry for completed steps.
        $completedStep = ObjectWorkflowCompletedStep::fetch([Account::class, 13, "manualCheck", "N/A"]);
        $this->assertEquals(date("Y-m-d H:i:s"), $completedStep->getCompletedTime()->format("Y-m-d H:i:s"));
        $this->assertEquals(ObjectWorkflowCompletedStep::STATUS_FAILED, $completedStep->getStatus());
        $this->assertEquals("Bad Action", $completedStep->getLogOutput());
        $this->assertEquals("N/A", $completedStep->getTriggerValue());

    }

    public function testAlreadySuccessfullyProcessedWorkflowStepsCannotBeProcessed() {


        // Program return value
        $this->task->returnValue("run", "Sample Output", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "manualCheck"]), "objectPk" => 14]]);

        // process a manual step
        $this->service->processWorkflowStep(Account::class, 14, "manualCheck");

        $this->task->resetMethodCallHistory("run");

        $this->task->returnValue("run", "Other Output", ["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "manualCheck"]), "objectPk" => 14]);

        // process a manual step
        $this->service->processWorkflowStep(Account::class, 14, "manualCheck");

        $this->assertFalse($this->task->methodWasCalled("run", ["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "manualCheck"]), "objectPk" => 14]));

        $completedStep = ObjectWorkflowCompletedStep::fetch([Account::class, 14, "manualCheck", "N/A"]);
        $this->assertEquals(ObjectWorkflowCompletedStep::STATUS_COMPLETED, $completedStep->getStatus());
        $this->assertEquals("Sample Output", $completedStep->getLogOutput());


    }


    public function testFailedPreviousProcessedWorkflowStepsCanBeProcessed() {

        // Program return value
        $this->task->throwException("run", new \Exception("Bad Action"), [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "manualCheck"]), "objectPk" => 15]]);

        // process a manual step
        $this->service->processWorkflowStep(Account::class, 15, "manualCheck");

        $this->task->resetMethodCallHistory("run");

        $this->task->returnValue("run", "Other Output", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "manualCheck"]), "objectPk" => 15]]);

        // process a manual step
        $this->service->processWorkflowStep(Account::class, 15, "manualCheck");

        $this->assertTrue($this->task->methodWasCalled("run", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "manualCheck"]), "objectPk" => 15]]));

        $completedStep = ObjectWorkflowCompletedStep::fetch([Account::class, 15, "manualCheck", "N/A"]);
        $this->assertEquals(ObjectWorkflowCompletedStep::STATUS_COMPLETED, $completedStep->getStatus());
        $this->assertEquals("Other Output", $completedStep->getLogOutput());

    }


    public function testCanProcessDueTasksForObjectWorkflowsBasedOnDates() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $newAccount = new Account("Workflow Example", 0);
        $newAccount->setStatus(Account::STATUS_ACTIVE);
        $newAccount->save();
        $accountId = $newAccount->getAccountId();
        $createdDate = $newAccount->getCreatedDate()->format("Y-m-d H:i:s");

        // Program return value
        $this->task->returnValue("run", "First Created", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "created-first"]), "objectPk" => $accountId]]);
        $this->task->returnValue("run", "Second Created", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "created-second"]), "objectPk" => $accountId]]);

        // Process due workflow steps
        $this->service->processDueWorkflowStepsForObjectClass(Account::class);

        // Check that we have a completed entry for new account
        $completedEntry = ObjectWorkflowCompletedStep::fetch([Account::class, $accountId, "created-first", $createdDate]);
        $this->assertEquals(ObjectWorkflowCompletedStep::STATUS_COMPLETED, $completedEntry->getStatus());
        $this->assertEquals("First Created", $completedEntry->getLogOutput());
        $this->assertEquals($createdDate, $completedEntry->getTriggerValue());

        // Check no completed entry for created second yet

        $this->assertEmpty(ObjectWorkflowCompletedStep::filter("WHERE object_class = ? AND object_pk = ? AND step_key = ?", Account::class, $accountId, "created-second"));


        $date = new \DateTime();
        $date->sub(new \DateInterval("P1D"));
        $date = $date->format("Y-m-d 00:00:00");

        $this->databaseConnection->query("UPDATE ka_account SET created_date = ? WHERE account_id = ?", $date, $accountId);


        // Process due workflow steps
        $this->service->processDueWorkflowStepsForObjectClass(Account::class);

        // Check that we have a completed entry for new account
        $completedEntry = ObjectWorkflowCompletedStep::fetch([Account::class, $accountId, "created-second", $date]);
        $this->assertEquals(ObjectWorkflowCompletedStep::STATUS_COMPLETED, $completedEntry->getStatus());
        $this->assertEquals("Second Created", $completedEntry->getLogOutput());
        $this->assertEquals($date, $completedEntry->getTriggerValue());


    }


    public function testWorkflowNotProcessedTwiceIfAlreadyRun() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $newAccount = new Account("Workflow Example", 0);
        $newAccount->setStatus(Account::STATUS_ACTIVE);
        $newAccount->save();
        $accountId = $newAccount->getAccountId();
        $createdDate = $newAccount->getCreatedDate()->format("Y-m-d H:i:s");


        // Program return value
        $this->task->returnValue("run", "First Created", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "created-first"]), "objectPk" => $accountId]]);

        // Process due workflow steps
        $this->service->processDueWorkflowStepsForObjectClass(Account::class);

        // Check that we have a completed entry for new account
        $completedEntry = ObjectWorkflowCompletedStep::fetch([Account::class, $accountId, "created-first", $createdDate]);
        $this->assertEquals(ObjectWorkflowCompletedStep::STATUS_COMPLETED, $completedEntry->getStatus());
        $this->assertEquals("First Created", $completedEntry->getLogOutput());
        $this->assertEquals($createdDate, $completedEntry->getTriggerValue());


        $this->task->returnValue("run", "First Created Updated", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "created-first"]), "objectPk" => $accountId]]);

        // Process due workflow steps
        $this->service->processDueWorkflowStepsForObjectClass(Account::class);

        // Check that we have a completed entry for new account
        $completedEntry = ObjectWorkflowCompletedStep::fetch([Account::class, $accountId, "created-first", $createdDate]);
        $this->assertEquals(ObjectWorkflowCompletedStep::STATUS_COMPLETED, $completedEntry->getStatus());
        $this->assertEquals("First Created", $completedEntry->getLogOutput());
        $this->assertEquals($createdDate, $completedEntry->getTriggerValue());


    }


    public function testTasksReProcessedForObjectWorkflowsIfTriggerDatesChange() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $newAccount = new Account("Workflow Example", 0);
        $newAccount->setStatus(Account::STATUS_ACTIVE);
        $newAccount->save();
        $accountId = $newAccount->getAccountId();
        $createdDate = $newAccount->getCreatedDate()->format("Y-m-d H:i:s");


        // Program return value
        $this->task->returnValue("run", "First Created", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "created-first"]), "objectPk" => $accountId]]);
        $this->task->returnValue("run", "Second Created", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "created-second"]), "objectPk" => $accountId]]);

        // Process due workflow steps
        $this->service->processDueWorkflowStepsForObjectClass(Account::class);

        // Check that we have a completed entry for new account
        $completedEntry = ObjectWorkflowCompletedStep::fetch([Account::class, $accountId, "created-first", $createdDate]);
        $this->assertEquals(ObjectWorkflowCompletedStep::STATUS_COMPLETED, $completedEntry->getStatus());
        $this->assertEquals("First Created", $completedEntry->getLogOutput());
        $this->assertEquals($createdDate, $completedEntry->getTriggerValue());

        // Check no completed entry for created second yet
        $this->assertEmpty(ObjectWorkflowCompletedStep::filter("WHERE object_class = ? AND object_pk = ? AND step_key = ?", Account::class, $accountId, "created-second"));


        $date = new \DateTime();
        $date->sub(new \DateInterval("PT1H"));
        $date = $date->format("Y-m-d H:i:s");

        $this->databaseConnection->query("UPDATE ka_account SET created_date = ? WHERE account_id = ?", $date, $accountId);


        // Process due workflow steps
        $this->service->processDueWorkflowStepsForObjectClass(Account::class);

        // Check that we have a re-completed first entry for new account
        $completedEntry = ObjectWorkflowCompletedStep::fetch([Account::class, $accountId, "created-first", $date]);
        $this->assertEquals(ObjectWorkflowCompletedStep::STATUS_COMPLETED, $completedEntry->getStatus());
        $this->assertEquals("First Created", $completedEntry->getLogOutput());
        $this->assertEquals($date, $completedEntry->getTriggerValue());

        // Check no completed entry for created second yet
        $this->assertEmpty(ObjectWorkflowCompletedStep::filter("WHERE object_class = ? AND object_pk = ? AND step_key = ?", Account::class, $accountId, "created-second"));



    }


    public function testCanProcessPropertyChangeWorkflowsForNewObjects() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Program return value
        $this->task->returnValue("run", "Property Changed", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "property-change"]), "objectPk" => 20]]);

        // Process due workflow steps
        $this->service->processPropertyChangeWorkflowSteps(Account::class, 20, null, new Account("Mark", 0, Account::STATUS_PENDING));

        // Check that we have a completed entry for new account
        $completedEntry = ObjectWorkflowCompletedStep::fetch([Account::class, 20, "property-change", md5(json_encode(Account::STATUS_PENDING).date("Y-m-d H:i:s"))]);
        $this->assertEquals(ObjectWorkflowCompletedStep::STATUS_COMPLETED, $completedEntry->getStatus());
        $this->assertEquals("Property Changed", $completedEntry->getLogOutput());
        $this->assertEquals( md5(json_encode(Account::STATUS_PENDING).date("Y-m-d H:i:s")), $completedEntry->getTriggerValue());


    }


    public function testPropertyChangeWorkflowOnlyCalledIfChangeMadeToExistingPropertyOnExistingObjects() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Program return value
        $this->task->returnValue("run", "Property Changed", [["workflowStep" => ObjectWorkflowStep::fetch([Account::class, "property-change"]), "objectPk" => 21]]);

        // Process due workflow steps
        $this->service->processPropertyChangeWorkflowSteps(Account::class, 21,
            new Account("Mark", 0, Account::STATUS_PENDING),
            new Account("Mark", 0, Account::STATUS_PENDING));


        // Check no completed entry for created second yet
        $this->assertEmpty(ObjectWorkflowCompletedStep::filter("WHERE object_class = ? AND object_pk = ? AND step_key = ?", Account::class, 21, "property-change"));

        // Process due workflow steps
        $this->service->processPropertyChangeWorkflowSteps(Account::class, 21,
            new Account("Mark", 0, Account::STATUS_PENDING),
            new Account("Mark", 0, Account::STATUS_ACTIVE));


        // Check that we have a completed entry for new account
        $completedEntry = ObjectWorkflowCompletedStep::fetch([Account::class, 21, "property-change", md5(json_encode(Account::STATUS_ACTIVE) . date("Y-m-d H:i:s"))]);
        $this->assertEquals(ObjectWorkflowCompletedStep::STATUS_COMPLETED, $completedEntry->getStatus());
        $this->assertEquals("Property Changed", $completedEntry->getLogOutput());
        $this->assertEquals( md5(json_encode(Account::STATUS_ACTIVE) . date("Y-m-d H:i:s")), $completedEntry->getTriggerValue());


    }

}