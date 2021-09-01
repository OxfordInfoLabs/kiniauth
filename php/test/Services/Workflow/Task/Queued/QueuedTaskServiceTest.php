<?php

namespace Kiniauth\Services\Workflow\Task\Queued;

use Kiniauth\Exception\QueuedTask\NoQueuedTaskImplementationException;
use Kiniauth\Services\Workflow\Task\Queued\Processor\QueuedTaskProcessor;
use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kiniauth\Services\Workflow\Task\Task;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\QueuedTask\QueueItem;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;

include_once "autoloader.php";

/**
 * Test cases for the Queued task service
 *
 * Class QueuedTaskServiceTest
 */
class QueuedTaskServiceTest extends TestBase {

    /**
     * @var QueuedTaskService
     */
    private $queuedTaskService;

    /**
     * @var MockObject
     */
    private $mockQueuedTaskProcessor;


    /**
     * @var MockObject
     */
    private $mockQueuedTask;


    public function setUp(): void {

        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);
        $this->mockQueuedTaskProcessor = $mockObjectProvider->getMockInstance(QueuedTaskProcessor::class);

        $this->queuedTaskService = new QueuedTaskService($this->mockQueuedTaskProcessor);

        // Set mock task to respond to our test configured one.
        $this->mockQueuedTask = $mockObjectProvider->getMockInstance(Task::class);
        Container::instance()->set("MyLittlePony", $this->mockQueuedTask);

    }


    public function testCanQueueTaskAndInstalledQueueTaskProcessorIsUsed() {

        $this->queuedTaskService->queueTask("mylittlepony", "chillpill", 'Take a chill pill', ["hello" => "world"]);
        $this->assertTrue($this->mockQueuedTaskProcessor->methodWasCalled("queueTask", [
            "mylittlepony",
            "chillpill",
            "Take a chill pill",
            ["hello" => "world"],
            null
        ]));


        $scheduleDate = date_create_from_format("d/m/Y H:i:s", "01/01/2020 10:20:33");
        $offsetDate = new \DateTime();
        $offsetDate->add(new \DateInterval("PT300S"));

        // Scheduled one.
        $this->queuedTaskService->queueTask("newsite", "testing", "Testing", [], $scheduleDate);

        $this->assertTrue($this->mockQueuedTaskProcessor->methodWasCalled("queueTask", [
            "newsite",
            "testing",
            "Testing",
            [],
            $scheduleDate
        ]));


        // Offset one.
        $this->mockQueuedTaskProcessor->resetMethodCallHistory("queueTask");
        $this->queuedTaskService->queueTask("newsite", "testing", "Testing", [], null, 300);

        $this->assertTrue($this->mockQueuedTaskProcessor->methodWasCalled("queueTask"));

        $history = $this->mockQueuedTaskProcessor->getMethodCallHistory("queueTask");
        $this->assertEquals($offsetDate->format("d/m/Y H:i:s"), $history[0][4]->format("d/m/Y H:i:s"));
    }


    public function testNoQueuedTaskImplementationExceptionRaisedWhenTryingToProcessANonDefinedTask() {

        try {
            $this->queuedTaskService->processQueuedTask("testqueue", "nonexistent", "12345");
            $this->fail("Should have thrown here");
        } catch (NoQueuedTaskImplementationException $e) {
            $this->assertTrue(true);
        }

    }

    public function testProcessQueuedTaskExecutesTheConfiguredTaskAndUpdatesStatus() {

        $this->queuedTaskService->processQueuedTask("testqueue", "mylittlepony", "12345", ["temp" => "test"]);

        $this->assertTrue($this->mockQueuedTaskProcessor->methodWasCalled("registerTaskStatusChange",
            [
                "testqueue",
                "12345",
                QueueItem::STATUS_RUNNING
            ]));

        $this->assertTrue($this->mockQueuedTask->methodWasCalled("run", [["temp" => "test"]]));

        $this->assertTrue($this->mockQueuedTaskProcessor->methodWasCalled("registerTaskStatusChange",
            [
                "testqueue",
                "12345",
                QueueItem::STATUS_COMPLETED
            ]));

    }


}
