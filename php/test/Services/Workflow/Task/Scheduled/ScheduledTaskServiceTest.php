<?php


namespace Kiniauth\Test\Services\Workflow\Task\Scheduled;


use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskInterceptor;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Services\Workflow\Task\Scheduled\Processor\ScheduledTaskProcessor;
use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;
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

        $this->assertEquals($expectedNextStartTime, $task->getNextStartTime());
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
            ], null, $nextStartTime), null, 1);
        $task1->save();
        $task1Id = $task1->getId();


        $task2 = new ScheduledTask(new ScheduledTaskSummary("test", "Test Scheduled Task",
            ["myParam" => "Hello", "anotherParam" => "Goodbye"], [
                new ScheduledTaskTimePeriod(null, 3, 15, 30)
            ], null, $nextStartTime), null, 1);
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
            ], null, $nextStartTime), null, 1);
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


}