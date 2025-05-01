<?php


namespace Kiniauth\Test\Services\Workflow\Task\Scheduled\Processor;

use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskInterceptor;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskLog;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Services\Workflow\Task\Scheduled\Processor\ScheduledTaskProcessor;
use Kiniauth\Services\Workflow\Task\Task;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\ConcreteClassGenerator;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Util\TaskManager;
use Kinikit\Persistence\Database\Connection\DatabaseConnectionProvider;

include_once "autoloader.php";


class ScheduledTaskProcessorTest extends TestBase {

    /**
     * @var ScheduledTaskProcessor
     */
    private $processor;

    /**
     * @var TaskManager
     */
    private $mockTaskManager;

    private $originalTaskManager;


    public function setUp(): void {
        $this->processor = ConcreteClassGenerator::instance()->generateInstance(ScheduledTaskProcessor::class);

        $this->originalTaskManager = Container::instance()->get(TaskManager::class);
        $this->mockTaskManager = MockObjectProvider::mock(TaskManager::class);
        Container::instance()->set(TaskManager::class, $this->mockTaskManager);
    }

    public function tearDown(): void {
        Container::instance()->set(TaskManager::class, $this->originalTaskManager);
    }


    public function testOnSuccessfulProcessOfTaskStartAndEndTimesAreStampedWithStatusAndLogEntryCreated() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $successTask = MockObjectProvider::instance()->getMockInstance(Task::class);
        $successTask->returnValue("run", [
            "result" => "Hello"
        ], [
            ["game" => "set"]
        ]);

        Container::instance()->addInterfaceImplementation(Task::class, "success", get_class($successTask));
        Container::instance()->set(get_class($successTask), $successTask);

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("success", "Successful task", ["game" => "set"], [new ScheduledTaskTimePeriod(null, null, 0, 0)], ScheduledTaskSummary::STATUS_PENDING,
            null, null, null, null, 3600), null, 1);

        // Process the scheduled task
        $scheduledTask = $this->processor->processScheduledTask($scheduledTask);

        // Check the scheduled task updated as expected and saved and that a log entry was created
        $this->assertNotNull($scheduledTask->getId());
        $this->assertEquals(ScheduledTaskSummary::STATUS_COMPLETED, $scheduledTask->getStatus());
        $this->assertNotNull($scheduledTask->getLastStartTime());
        $this->assertNotNull($scheduledTask->getLastEndTime());
        $expectedDate = date_create_from_format("d/m/Y H:i:s", date("d/m/Y") . " 00:00:00");
        $expectedDate->add(new \DateInterval("P1D"));
        $this->assertEquals($expectedDate, $scheduledTask->getNextStartTime());
        $expectedTimeout = (new \DateTime())->add(new \DateInterval("PT3600S"));
        $this->assertLessThan(2, $scheduledTask->getTimeoutTime()->format("U") - $expectedTimeout->format("U"));
        $this->assertNull($scheduledTask->getPid());

        // Check for log entry as well
        $logEntries = ScheduledTaskLog::filter("WHERE scheduled_task_id = " . $scheduledTask->getId());
        $this->assertEquals(1, sizeof($logEntries));
        $logEntry = $logEntries[0];
        $this->assertEquals($scheduledTask->getLastStartTime(), $logEntry->getStartTime());
        $this->assertEquals($scheduledTask->getLastEndTime(), $logEntry->getEndTime());
        $this->assertEquals(ScheduledTaskSummary::STATUS_COMPLETED, $logEntry->getStatus());
        $this->assertEquals([
            "result" => "Hello"
        ], $logEntry->getLogOutput());


    }


    public function testIfScheduledTaskHasStartTimeUpdatedWhilstRunningNoChangeIsMadeToTheScheduledTaskStartTimeAtTheEnd() {


        AuthenticationHelper::login("admin@kinicart.com", "password");

        Container::instance()->addInterfaceImplementation(Task::class, "dateupdate", DateUpdatingProcessorTask::class);

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("dateupdate", "Successful task", [], [new ScheduledTaskTimePeriod(null, null, 0, 0)], ScheduledTaskSummary::STATUS_PENDING,
            null, null, null, null, 3600), null, 1);

        ScheduledTaskInterceptor::$disabled = true;
        $scheduledTask->save();
        $scheduledTask->setConfiguration(["date" => "2035-01-01 10:00:00", "id" => $scheduledTask->getId()]);
        $scheduledTask->save();
        ScheduledTaskInterceptor::$disabled = false;

        // Process the scheduled task
        $scheduledTask = $this->processor->processScheduledTask($scheduledTask);

        // Check the scheduled task updated as expected and saved and that a log entry was created
        $this->assertNotNull($scheduledTask->getId());
        $this->assertEquals(ScheduledTaskSummary::STATUS_COMPLETED, $scheduledTask->getStatus());
        $this->assertNotNull($scheduledTask->getLastStartTime());
        $this->assertNotNull($scheduledTask->getLastEndTime());
        $this->assertNull($scheduledTask->getPid());

        // Check explicit date set
        $this->assertEquals(date_create_from_format("Y-m-d H:i:s", "2035-01-01 10:00:00"), $scheduledTask->getNextStartTime());

        // Check timeout still intact
        $expectedTimeout = (new \DateTime())->add(new \DateInterval("PT3600S"));
        $this->assertLessThan(2, $scheduledTask->getTimeoutTime()->format("U") - $expectedTimeout->format("U"));


    }


    public function testOnUnSuccessfulProcessOfTaskStartAndEndTimesAreStampedWithStatusAndLogEntryCreated() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $failedTask = MockObjectProvider::instance()->getMockInstance(Task::class);
        $failedTask->throwException("run", new \Exception("Cannot run"), [
            ["game" => "set"]
        ]);

        Container::instance()->addInterfaceImplementation(Task::class, "success", get_class($failedTask));
        Container::instance()->set(get_class($failedTask), $failedTask);

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("success", "Successful task", ["game" => "set"], [
            new ScheduledTaskTimePeriod(null, null, 0, 0)], ScheduledTaskSummary::STATUS_PENDING, null, null, null, null, 100), null, 1);

        // Process the scheduled task
        $scheduledTask = $this->processor->processScheduledTask($scheduledTask);

        // Check the scheduled task updated as expected and saved and that a log entry was created
        $this->assertNotNull($scheduledTask->getId());
        $this->assertEquals(ScheduledTaskSummary::STATUS_FAILED, $scheduledTask->getStatus());
        $this->assertNotNull($scheduledTask->getLastStartTime());
        $this->assertNotNull($scheduledTask->getLastEndTime());
        $this->assertNull($scheduledTask->getPid());
        $expectedDate = date_create_from_format("d/m/Y H:i:s", date("d/m/Y") . " 00:00:00");
        $expectedDate->add(new \DateInterval("P1D"));
        $this->assertEquals($expectedDate, $scheduledTask->getNextStartTime());
        $expectedTimeout = (new \DateTime())->add(new \DateInterval("PT100S"));
        $this->assertLessThan(2, $scheduledTask->getTimeoutTime()->format("U") - $expectedTimeout->format("U"));

        // Check for log entry as well
        $logEntries = ScheduledTaskLog::filter("WHERE scheduled_task_id = " . $scheduledTask->getId());
        $this->assertEquals(1, sizeof($logEntries));
        $logEntry = $logEntries[0];
        $this->assertEquals($scheduledTask->getLastStartTime(), $logEntry->getStartTime());
        $this->assertEquals($scheduledTask->getLastEndTime(), $logEntry->getEndTime());
        $this->assertEquals(ScheduledTaskSummary::STATUS_FAILED, $logEntry->getStatus());
        $this->assertEquals("Cannot run", $logEntry->getLogOutput());

    }

    public function testIfScheduledTaskAlreadyStartedItIsSkipped() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $successTask = MockObjectProvider::instance()->getMockInstance(Task::class);
        $successTask->returnValue("run", [
            "result" => "Hello"
        ], [
            ["game" => "set"]
        ]);

        Container::instance()->addInterfaceImplementation(Task::class, "success", get_class($successTask));
        Container::instance()->set(get_class($successTask), $successTask);

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("success", "Successful task", ["game" => "set"], []), null, 1);
        $scheduledTask->save();


        // Now set as running behind our back
        $reTask = ScheduledTask::fetch($scheduledTask->getId());
        $reTask->setStatus(ScheduledTask::STATUS_RUNNING);
        $reTask->setPid(12345);
        $reTask->save();

        // Process the scheduled task
        $scheduledTask = $this->processor->processScheduledTask($scheduledTask);

        // Check it was ignored and not updated
        $this->assertNull($scheduledTask->getLastStartTime());
        $this->assertNull($scheduledTask->getLastEndTime());
        $this->assertEquals(ScheduledTask::STATUS_RUNNING, $scheduledTask->getStatus());
        $this->assertEquals(12345, $scheduledTask->getPid());

    }


    public function testIfScheduledTaskAlreadyCompletedWithFutureNextStartDateItIsSkipped() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $successTask = MockObjectProvider::instance()->getMockInstance(Task::class);
        $successTask->returnValue("run", [
            "result" => "Hello"
        ], [
            ["game" => "set"]
        ]);

        Container::instance()->addInterfaceImplementation(Task::class, "success", get_class($successTask));
        Container::instance()->set(get_class($successTask), $successTask);

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("success", "Successful task", ["game" => "set"], []), null, 1);
        $scheduledTask->save();

        /**
         * @var $databaseConnectionProvider DatabaseConnectionProvider
         */
        $databaseConnectionProvider = Container::instance()->get(DatabaseConnectionProvider::class);

        $databaseConnection = $databaseConnectionProvider->getDatabaseConnectionByConfigKey(null);

        $nextStartTime = new \DateTime();
        $nextStartTime->sub(new \DateInterval("P1D"));
        $databaseConnection->execute("UPDATE ka_scheduled_task SET next_start_time = ? WHERE id = ?", $nextStartTime->format("Y-m-d H:i:s"), $scheduledTask->getId());
        $scheduledTask = ScheduledTask::fetch($scheduledTask->getId());

        $nextStartTime = new \DateTime();
        $nextStartTime->add(new \DateInterval("P1D"));
        $databaseConnection->execute("UPDATE ka_scheduled_task SET next_start_time = ? WHERE id = ?", $nextStartTime->format("Y-m-d H:i:s"), $scheduledTask->getId());

        // Process the scheduled task
        $scheduledTask = $this->processor->processScheduledTask($scheduledTask);


        // Check it was ignored and not updated
        $this->assertNull($scheduledTask->getLastStartTime());
        $this->assertNull($scheduledTask->getLastEndTime());
        $this->assertEquals(ScheduledTask::STATUS_PENDING, $scheduledTask->getStatus());
        $this->assertNull($scheduledTask->getPid());

    }

    public function testDoesKillTasksMarkedAsKilling() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $successTask = MockObjectProvider::instance()->getMockInstance(Task::class);
        $successTask->returnValue("run", [
            "result" => "Hello"
        ], [
            ["game" => "set"]
        ]);

        Container::instance()->addInterfaceImplementation(Task::class, "success", get_class($successTask));
        Container::instance()->set(get_class($successTask), $successTask);

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("success", "Successful task", ["game" => "set"],
            [new ScheduledTaskTimePeriod(null, null, 0, 0)],
            ScheduledTaskSummary::STATUS_KILLING, null, '2000-01-01 00:00:00', null, null, 3600, pid: 123), null, 1);

        // Process the scheduled task
        $scheduledTask = $this->processor->processScheduledTask($scheduledTask);

        // Check the scheduled task updated as expected and saved
        $this->assertNotNull($scheduledTask->getId());
        $this->assertEquals(ScheduledTaskSummary::STATUS_KILLED, $scheduledTask->getStatus());
        $this->assertNotNull($scheduledTask->getLastEndTime());
        $expectedDate = date_create_from_format("d/m/Y H:i:s", date("d/m/Y") . " 00:00:00");
        $expectedDate->add(new \DateInterval("P1D"));
        $this->assertEquals($expectedDate, $scheduledTask->getNextStartTime());
        $this->assertNull($scheduledTask->getPid());

        // Check the task manager kill function called
        $this->assertTrue($this->mockTaskManager->methodWasCalled("killProcess", [123]));

        // Check for log entry as well
        $logEntries = ScheduledTaskLog::filter("WHERE scheduled_task_id = " . $scheduledTask->getId());
        $this->assertEquals(1, sizeof($logEntries));
        $logEntry = $logEntries[0];
        $this->assertEquals($scheduledTask->getLastStartTime(), $logEntry->getStartTime());
        $this->assertEquals($scheduledTask->getLastEndTime(), $logEntry->getEndTime());
        $this->assertEquals(ScheduledTaskSummary::STATUS_KILLED, $logEntry->getStatus());
        $this->assertEquals('Task Killed.', $logEntry->getLogOutput());

    }


}