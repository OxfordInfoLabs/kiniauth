<?php


namespace Kiniauth\Test\Services\Workflow\Task\Scheduled\Processor;

use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskLog;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Services\Workflow\Task\Scheduled\Processor\ScheduledTaskProcessor;
use Kiniauth\Services\Workflow\Task\Task;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\Proxy;
use Kinikit\Core\Proxy\ProxyGenerator;
use Kinikit\Core\Testing\ConcreteClassGenerator;
use Kinikit\Core\Testing\MockObjectProvider;

include_once "autoloader.php";


class ScheduledTaskProcessorTest extends TestBase {

    /**
     * @var ScheduledTaskProcessor
     */
    private $processor;


    public function setUp(): void {
        $this->processor = ConcreteClassGenerator::instance()->generateInstance(ScheduledTaskProcessor::class);
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

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("success", "Successful task", ["game" => "set"], [new ScheduledTaskTimePeriod(null, null, 0, 0)]), null, 1);

        // Process the scheduled task
        $this->processor->processScheduledTask($scheduledTask);

        // Check the scheduled task updated as expected and saved and that a log entry was created
        $this->assertNotNull($scheduledTask->getId());
        $this->assertEquals(ScheduledTaskSummary::STATUS_COMPLETED, $scheduledTask->getStatus());
        $this->assertNotNull($scheduledTask->getLastStartTime());
        $this->assertNotNull($scheduledTask->getLastEndTime());
        $expectedDate = date_create_from_format("d/m/Y H:i:s", date("d/m/Y") . " 00:00:00");
        $expectedDate->add(new \DateInterval("P1D"));
        $this->assertEquals($expectedDate, $scheduledTask->getNextStartTime());

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


    public function testOnUnSuccessfulProcessOfTaskStartAndEndTimesAreStampedWithStatusAndLogEntryCreated() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $failedTask = MockObjectProvider::instance()->getMockInstance(Task::class);
        $failedTask->throwException("run", new \Exception("Cannot run"), [
            ["game" => "set"]
        ]);

        Container::instance()->addInterfaceImplementation(Task::class, "success", get_class($failedTask));
        Container::instance()->set(get_class($failedTask), $failedTask);

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("success", "Successful task", ["game" => "set"], [
            new ScheduledTaskTimePeriod(null, null, 0, 0)]), null, 1);

        // Process the scheduled task
        $this->processor->processScheduledTask($scheduledTask);

        // Check the scheduled task updated as expected and saved and that a log entry was created
        $this->assertNotNull($scheduledTask->getId());
        $this->assertEquals(ScheduledTaskSummary::STATUS_FAILED, $scheduledTask->getStatus());
        $this->assertNotNull($scheduledTask->getLastStartTime());
        $this->assertNotNull($scheduledTask->getLastEndTime());
        $expectedDate = date_create_from_format("d/m/Y H:i:s", date("d/m/Y") . " 00:00:00");
        $expectedDate->add(new \DateInterval("P1D"));
        $this->assertEquals($expectedDate, $scheduledTask->getNextStartTime());

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
        $reTask->save();

        // Process the scheduled task
        $this->processor->processScheduledTask($scheduledTask);

        // Check it was ignored and not updated
        $this->assertNull($scheduledTask->getLastStartTime());
        $this->assertNull($scheduledTask->getLastEndTime());
        $this->assertEquals(ScheduledTask::STATUS_PENDING, $scheduledTask->getStatus());


    }


}