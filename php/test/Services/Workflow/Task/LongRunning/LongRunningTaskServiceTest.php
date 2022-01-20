<?php


namespace Kiniauth\Test\Services\Workflow\Task\LongRunning;


use Kiniauth\Objects\Workflow\Task\LongRunning\StoredLongRunningTask;
use Kiniauth\Services\Workflow\Task\LongRunning\LongRunningTaskService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

include_once "autoloader.php";

class LongRunningTaskServiceTest extends TestBase {


    /**
     * @var LongRunningTaskService
     */
    private $longRunningTaskService;

    // Set up
    public function setUp(): void {
        $this->longRunningTaskService = Container::instance()->get(LongRunningTaskService::class);
    }

    public function testSuccessfulLongRunningTaskCanBeStartedAndIsMarkedAccordingly() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $longRunningTask = new TestLongRunningTask();

        // Check task runs successfully
        $result = $this->longRunningTaskService->startTask("Test", $longRunningTask, "FFFEEE", null, 2,
            60, 60);
        $this->assertEquals("SUCCESS", $result);


        /**
         * @var StoredLongRunningTask $task
         */
        $task = StoredLongRunningTask::filter("WHERE task_key = ?", "FFFEEE")[0];
        $this->assertEquals(2, $task->getAccountId());
        $this->assertEquals("Test", $task->getTaskIdentifier());
        $this->assertEquals(StoredLongRunningTask::STATUS_COMPLETED, $task->getStatus());
        $this->assertNotNull($task->getStartedDate());
        $this->assertNotNull($task->getFinishedDate());
        $this->assertEquals("SUCCESS", $task->getResult());

        // Check expiry date correct
        $expiryDate = $task->getExpiryDate();
        $this->assertEquals(3600, $expiryDate->format("U") - $task->getFinishedDate()->format("U"));

        // Check timeout date correct
        $timeoutDate = $task->getTimeoutDate();
        $this->assertEquals(60, $timeoutDate->format("U") - $task->getStartedDate()->format("U"));


    }


    public function testFailingLongRunningTaskCanBeStartedAndIsMarkedAccordingly() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $longRunningTask = new TestLongRunningTask(false);

        // Check task failed
        try {
            $this->longRunningTaskService->startTask("Test", $longRunningTask, "GGGFFF", "soapSuds", 2,
                60, 60);
            $this->fail("Should have thrown here");

        } catch (\Exception $e) {
            // As expected
        }

        /**
         * @var StoredLongRunningTask $task
         */
        $task = StoredLongRunningTask::filter("WHERE task_key = ?", "GGGFFF")[0];
        $this->assertEquals(2, $task->getAccountId());
        $this->assertEquals("soapSuds", $task->getProjectKey());
        $this->assertEquals("Test", $task->getTaskIdentifier());
        $this->assertEquals(StoredLongRunningTask::STATUS_FAILED, $task->getStatus());
        $this->assertNotNull($task->getStartedDate());
        $this->assertNotNull($task->getFinishedDate());
        $this->assertEquals("Long running task failure", $task->getResult());

        // Check expiry date correct
        $expiryDate = $task->getExpiryDate();
        $this->assertEquals(3600, $expiryDate->format("U") - $task->getFinishedDate()->format("U"));

        // Check timeout date correct
        $timeoutDate = $task->getTimeoutDate();
        $this->assertEquals(60, $timeoutDate->format("U") - $task->getStartedDate()->format("U"));


    }

    public function testCanGetAStoredTaskByTaskKey() {

        $longRunningTask = new TestLongRunningTask();

        // Check task runs successfully
        $this->longRunningTaskService->startTask("Test", $longRunningTask, "AAABBB", null, 2,
            60, 60);


        $task = $this->longRunningTaskService->getStoredTaskByTaskKey("AAABBB");
        $this->assertEquals("Test", $task->getTaskIdentifier());
        $this->assertEquals(StoredLongRunningTask::STATUS_COMPLETED, $task->getStatus());
        $this->assertEquals("AAABBB", $task->getTaskKey());
        $this->assertEquals("SUCCESS", $task->getResult());
        $this->assertNotNull($task->getStartedDate());
        $this->assertNotNull($task->getFinishedDate());
        $expiryDate = $task->getExpiryDate();
        $this->assertEquals(3600, $expiryDate->format("U") - $task->getFinishedDate()->format("U"));


        try {
            $this->longRunningTaskService->getStoredTaskByTaskKey("BADKEY");
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }

    }

    public function testCanProcessLongRunningTasksWhichHaveTimedOut() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");


        $longRunningTask1 = new StoredLongRunningTask("TEST", "TIMEOUT1", 10, 0, null, 1);
        $longRunningTask1->save();

        $longRunningTask2 = new StoredLongRunningTask("TEST", "TIMEOUT2", 10, 2000, null, 1);
        $longRunningTask2->save();

        $longRunningTask3 = new StoredLongRunningTask("TEST", "TIMEOUT3", 10, 0, null, 1);
        $longRunningTask3->save();

        // Process timeouts
        $this->longRunningTaskService->processTimeouts();

        // Expect 1 and 3 to be timed out
        $task1 = StoredLongRunningTask::filter("WHERE task_key = ?", "TIMEOUT1")[0];
        $this->assertNotNull($task1->getFinishedDate());
        $expiryDate = $task1->getExpiryDate();
        $this->assertEquals(600, $expiryDate->format("U") - $task1->getFinishedDate()->format("U"));
        $this->assertEquals(StoredLongRunningTask::STATUS_TIMEOUT, $task1->getStatus());


        $task2 = StoredLongRunningTask::filter("WHERE task_key = ?", "TIMEOUT2")[0];
        $this->assertEquals(StoredLongRunningTask::STATUS_RUNNING, $task2->getStatus());

        $task3 = StoredLongRunningTask::filter("WHERE task_key = ?", "TIMEOUT3")[0];
        $this->assertEquals(StoredLongRunningTask::STATUS_TIMEOUT, $task3->getStatus());
        $this->assertNotNull($task3->getFinishedDate());
        $expiryDate = $task3->getExpiryDate();
        $this->assertEquals(600, $expiryDate->format("U") - $task3->getFinishedDate()->format("U"));
        $this->assertEquals(StoredLongRunningTask::STATUS_TIMEOUT, $task3->getStatus());


    }


    public function testCanProcessExpiredLongRunningTasksAndRemoveThem() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $longRunningTask = new TestLongRunningTask();

        // Check task runs successfully
        $this->longRunningTaskService->startTask("Test", $longRunningTask, "EXPIRY1", null, 1,
            0, 60);

        $this->longRunningTaskService->startTask("Test", $longRunningTask, "EXPIRY2", null, 1,
            20, 60);

        $this->longRunningTaskService->startTask("Test", $longRunningTask, "EXPIRY3", null, 1,
            0, 60);

        // Process timeouts
        $this->longRunningTaskService->processExpiries();

        $this->assertEquals(0, sizeof(StoredLongRunningTask::filter("WHERE task_key = ?", "EXPIRY1")));
        $this->assertEquals(1, sizeof(StoredLongRunningTask::filter("WHERE task_key = ?", "EXPIRY2")));
        $this->assertEquals(0, sizeof(StoredLongRunningTask::filter("WHERE task_key = ?", "EXPIRY3")));


    }


}