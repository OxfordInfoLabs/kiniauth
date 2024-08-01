<?php


namespace Kiniauth\Test\Services\Workflow\Task\Scheduled;


use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskInterceptor;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskLog;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Services\Workflow\Task\Scheduled\Processor\ScheduledTaskProcessor;
use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

include_once "autoloader.php";

class ScheduledTaskServiceTest extends TestBase {

    /**
     * @var ScheduledTaskService
     */
    private $scheduledTaskService;

    /**
     * @var MockObject
     */
    private $scheduledTaskProcessor;

    /**
     * Set up method for tests
     */
    public function setUp(): void {
        $this->scheduledTaskProcessor = MockObjectProvider::instance()->getMockInstance(ScheduledTaskProcessor::class);
        $this->scheduledTaskService = new ScheduledTaskService($this->scheduledTaskProcessor);
    }


    public function testCanCreateReadUpdateAndDeleteScheduledTasks() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $date = new \DateTime();
        $weekDay = $date->add(new \DateInterval("P1D"))->format("N");
        $expectedNextStartTime = date_create_from_format("d/m/Y H:i", $date->format("d/m/Y 12:20"));
        $date = 1 + ($date->add(new \DateInterval("P1D"))->format("d") % 28);

        $newTaskSummary = new ScheduledTaskSummary("test", "Test Scheduled Task",
            ["myParam" => "Hello", "anotherParam" => "Goodbye"], [
                new ScheduledTaskTimePeriod($date, null, 15, 30),
                new ScheduledTaskTimePeriod(null, $weekDay, 12, 20)
            ]);


        $taskId = $this->scheduledTaskService->saveScheduledTask($newTaskSummary, null, 1);
        $this->assertNotNull($taskId);

        // Read the task
        $task = $this->scheduledTaskService->getScheduledTask($taskId);
        $this->assertInstanceOf(ScheduledTaskSummary::class, $task);
        $this->assertEquals("test", $task->getTaskIdentifier());
        $this->assertEquals("Test Scheduled Task", $task->getDescription());
        $this->assertEquals(["myParam" => "Hello", "anotherParam" => "Goodbye"], $task->getConfiguration());

        $this->assertEquals($expectedNextStartTime->format("Y-m-d H:i:s"), $task->getNextStartTime());
        $this->assertEquals(2, sizeof($task->getTimePeriods()));

        $this->assertEquals(new ScheduledTaskTimePeriod($date, null, 15, 30, $task->getTimePeriods()[0]->getId(),
            $task->getTimePeriods()[0]->getCreatedDate()),
            $task->getTimePeriods()[0]);

        $this->assertEquals(new ScheduledTaskTimePeriod(null, $weekDay, 12, 20, $task->getTimePeriods()[1]->getId(),
            $task->getTimePeriods()[1]->getCreatedDate()),
            $task->getTimePeriods()[1]);


        // Delete the task
        $this->scheduledTaskService->deleteScheduledTask($taskId);

        try {
            $this->scheduledTaskService->getScheduledTask($taskId);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // Success
        }

    }




    public function testNewlyCreatedTasksAreNotExecutedUntilFirstDuePoint() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $futureDate = 1 + ((date("d") + 1) % 27);
        $futureDay = 1 + ((date("N") + 1) % 7);
        $futureHour = 1 + ((date("H") + 1) % 24);
        $futureMinute = 1 + ((date("i") + 1) % 59);

        $task1Id = $this->scheduledTaskService->saveScheduledTask(new ScheduledTaskSummary("test", "Test Scheduled Task",
            ["myParam" => "Hello", "anotherParam" => "Goodbye"], [
                new ScheduledTaskTimePeriod($futureDate, null, 15, 30)
            ]), null, 1);

        $task2Id = $this->scheduledTaskService->saveScheduledTask(new ScheduledTaskSummary("test", "Test Scheduled Task",
            ["myParam" => "Hello", "anotherParam" => "Goodbye"], [
                new ScheduledTaskTimePeriod(null, $futureDay, 15, 30)
            ]), null, 1);

        $task3Id = $this->scheduledTaskService->saveScheduledTask(new ScheduledTaskSummary("test", "Test Scheduled Task",
            ["myParam" => "Hello", "anotherParam" => "Goodbye"], [
                new ScheduledTaskTimePeriod(null, null, $futureHour, 25)
            ]), null, 1);


        $task4Id = $this->scheduledTaskService->saveScheduledTask(new ScheduledTaskSummary("test", "Test Scheduled Task",
            ["myParam" => "Hello", "anotherParam" => "Goodbye"], [
                new ScheduledTaskTimePeriod(null, null, null, $futureMinute)
            ]), null, 1);


        // Process due tasks
        $this->scheduledTaskService->processDueTasks();

        // Check no call was made to actually process scheduled tasks
        $this->assertFalse($this->scheduledTaskProcessor->methodWasCalled("processScheduledTasks"));

    }


    public function testIfDateHasPassedNextStartTimeForJobsTheyAreProcessed() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $nextStartTime = new \DateTime();
        $nextStartTime->sub(new \DateInterval("P1D"));

        ScheduledTaskInterceptor::$disabled = true;

        $task1 = new ScheduledTask(new ScheduledTaskSummary("test", "Test Scheduled Task",
            ["myParam" => "Hello", "anotherParam" => "Goodbye"], [
                new ScheduledTaskTimePeriod(20, null, 15, 30)
            ], null, $nextStartTime->format("Y-m-d H:i:s")), null, 1);
        $task1->save();
        $task1Id = $task1->getId();


        $task2 = new ScheduledTask(new ScheduledTaskSummary("test", "Test Scheduled Task",
            ["myParam" => "Hello", "anotherParam" => "Goodbye"], [
                new ScheduledTaskTimePeriod(null, 3, 15, 30)
            ], null, $nextStartTime->format("Y-m-d H:i:s")), null, 1);
        $task2->save();
        $task2Id = $task2->getId();

        $task3 = new ScheduledTask(new ScheduledTaskSummary("test", "Test Scheduled Task",
            ["myParam" => "Hello", "anotherParam" => "Goodbye"], [
                new ScheduledTaskTimePeriod(null, null, (new \DateTime())->add(new \DateInterval("PT1H"))->format("H"), 25)
            ], null, null), null, 1);
        $task3->recalculateNextStartTime();
        $task3->save();


        $task4 = new ScheduledTask(new ScheduledTaskSummary("test", "Test Scheduled Task",
            ["myParam" => "Hello", "anotherParam" => "Goodbye"], [
                new ScheduledTaskTimePeriod(null, null, null, 22)
            ], null, $nextStartTime->format("Y-m-d H:i:s")), null, 1);
        $task4->save();
        $task4Id = $task4->getId();


        // Process due tasks
        $this->scheduledTaskService->processDueTasks();


        $this->assertTrue($this->scheduledTaskProcessor->methodWasCalled("processScheduledTasks", [
            [
                ScheduledTask::fetch($task1Id),
                ScheduledTask::fetch($task2Id),
                ScheduledTask::fetch($task4Id)
            ]
        ]));


    }

    public function testCanIdentifyTimedOutTasksAndUpdateStatus() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        ScheduledTaskInterceptor::$disabled = false;

        $timeoutDate = (new \DateTime())->sub(new \DateInterval("PT2H"));

        $task1 = new ScheduledTask(new ScheduledTaskSummary("test", "Test Scheduled Task",
            ["myParam" => "Hello", "anotherParam" => "Goodbye"], [
                new ScheduledTaskTimePeriod(null, null, null, 30)
            ], ScheduledTaskSummary::STATUS_RUNNING, null, null, null, $timeoutDate->format("Y-m-d H:i:s"), 3600), null, 1);

        $task1->save();
        $task1Id = $task1->getId();

        // Process due tasks
        $this->scheduledTaskService->processDueTasks();

        $this->assertEquals(ScheduledTaskSummary::STATUS_TIMED_OUT, ScheduledTask::fetch($task1Id)->getStatus());
        $this->assertEquals(30, ScheduledTask::fetch($task1Id)->getNextStartTime()->format("i"));


    }

    public function testIfATaskTimeOutIsLoggedAndNextStartTimeUpdatedCorrectly() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $timeoutTime = (new \DateTime())->sub(new \DateInterval("PT10S"));
        $lastStartTime = (new \DateTime())->sub(new \DateInterval("PT1H"));
        $nextStartTime = (new \DateTime())->format("Y-m-d H:i:s");

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("test timeout", "Timed Out Task", ["game" => "set"], [new ScheduledTaskTimePeriod(null, null, 0, 0)], ScheduledTaskSummary::STATUS_RUNNING,
            $nextStartTime, $lastStartTime->format("Y-m-d H:i:s"), null, $timeoutTime->format("Y-m-d H:i:s"), 3500), null, 1);

        $scheduledTask->save();

        // Process due tasks
        $this->scheduledTaskService->processDueTasks();

        // Check the scheduled task updated as expected and saved and that a log entry was created
        $this->assertNotNull($scheduledTask->getId());
        $this->assertEquals(ScheduledTaskSummary::STATUS_RUNNING, $scheduledTask->getStatus());

        // Check for log entry as well
        $logEntries = ScheduledTaskLog::filter("WHERE scheduled_task_id = " . $scheduledTask->getId());
        $this->assertEquals(1, sizeof($logEntries));
        $logEntry = $logEntries[0];
        $this->assertEquals($scheduledTask->getLastStartTime(), $logEntry->getStartTime());
        $this->assertEquals($scheduledTask->getLastEndTime(), $logEntry->getEndTime());
        $this->assertEquals(ScheduledTaskSummary::STATUS_TIMED_OUT, $logEntry->getStatus());
        $this->assertEquals("Timed Out", $logEntry->getLogOutput());
    }

    public function testCanTriggerAScheduledTaskToRunImmediately() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $date = new \DateTime();
        $weekDay = $date->add(new \DateInterval("P1D"))->format("N");
        $expectedNextStartTime = date_create_from_format("d/m/Y H:i", $date->format("d/m/Y 12:20"));
        $date = 1 + ($date->add(new \DateInterval("P1D"))->format("d") % 28);

        $newTaskSummary = new ScheduledTaskSummary("test", "Test Scheduled Task",
            ["myParam" => "Hello", "anotherParam" => "Goodbye"], [
                new ScheduledTaskTimePeriod($date, null, 15, 30),
                new ScheduledTaskTimePeriod(null, $weekDay, 12, 20)
            ]);


        $taskId = $this->scheduledTaskService->saveScheduledTask($newTaskSummary, null, 1);
        $this->assertNotNull($taskId);

        $task = $this->scheduledTaskService->getScheduledTask($taskId);
        $this->assertEquals($expectedNextStartTime->format("Y-m-d H:i:s"), $task->getNextStartTime());

        $this->scheduledTaskService->triggerScheduledTask($taskId);

        $task = $this->scheduledTaskService->getScheduledTask($taskId);

        // Check that the next start time is reset and the status is pending
        $this->assertTrue(
            $task->getNextStartTime() == date("Y-m-d H:i:s") ||
            $task->getNextStartTime() == date("Y-m-d H:i:s", strtotime("-1 second"))            // In case we roll over a second during execution
        );
        $this->assertEquals(ScheduledTask::STATUS_PENDING, $task->getStatus());


    }

    public function testPassTaskGroupToProcessSubsetOfDueTasks() {

        AuthenticationHelper::login("admin@kinicart.com", "password");
        ScheduledTaskInterceptor::$disabled = true;

        $nextStartTime = (new \DateTime())->format("Y-m-d H:i:s");

        $myScheduledTask = new ScheduledTask(
            new ScheduledTaskSummary(
                "myFirstTask", "a task", null, [], nextStartTime: $nextStartTime, taskGroup: "myTasks"
            )
        );

        $yourScheduledTask = new ScheduledTask(
            new ScheduledTaskSummary(
                "yourFirstTask", "another task", null, [], nextStartTime: $nextStartTime, taskGroup: "yourTasks"
            )
        );

        $myScheduledTask->save();
        $yourScheduledTask->save();

        $myId = $myScheduledTask->getId();
        $yourId = $yourScheduledTask->getId();

        $this->scheduledTaskService->processDueTasks("myTasks");

        $this->assertTrue($this->scheduledTaskProcessor->methodWasCalled("processScheduledTasks", [[ScheduledTask::fetch($myId)]]));
        $this->assertFalse($this->scheduledTaskProcessor->methodWasCalled("processScheduledTasks", [[ScheduledTask::fetch($yourId)]]));

    }


}