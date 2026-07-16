<?php


namespace Kiniauth\Test\Services\Workflow\Task\Queued\Processor;

use Google\ApiCore\ApiException;
use Google\Cloud\Tasks\V2\Client\CloudTasksClient;
use Google\Cloud\Tasks\V2\HttpMethod;
use Google\Cloud\Tasks\V2\ListTasksRequest;
use Google\Cloud\Tasks\V2\Task\View;
use Kiniauth\Services\Workflow\Task\Queued\Processor\GoogleCloudQueuedTaskProcessor;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\QueuedTask\QueueItem;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;

include_once "autoloader.php";

class GoogleCloudQueuedTaskProcessorTest extends TestBase {

    /**
     * @var GoogleCloudQueuedTaskProcessor
     */
    private $queuedTaskProcessor;

    /**
     * @var CloudTasksClient
     */
    private $cloudTasksClient;


    /**
     * @var string
     */
    private $formattedQueue;

    public function setUp(): void {

        $this->queuedTaskProcessor = Container::instance()->get(GoogleCloudQueuedTaskProcessor::class);

        $this->cloudTasksClient = new CloudTasksClient([
            'credentials' => Configuration::readParameter("google.keyfile.path")
        ]);

        $this->formattedQueue = $this->cloudTasksClient->queueName('kinisite-test', 'europe-west2', 'kinisite-test');

        $listTaskRequests = new ListTasksRequest();
        $listTaskRequests->setParent($this->formattedQueue);

        foreach ($this->cloudTasksClient->listTasks($listTaskRequests)->getIterator() as $item) {
            $this->cloudTasksClient->deleteTask($item->getName());
        }

    }


    public function testCanQueueTask() {

        $identifier = $this->queuedTaskProcessor->queueTask("kinisite-test", "kinisite-test", "Test Oxford Cyber Test", ["myparam" => "tester"]);

        $task = $this->cloudTasksClient->getTask($identifier, ["responseView" => View::FULL]);
        $this->assertEquals($identifier, $task->getName());
        $this->assertEquals(HttpMethod::POST, $task->getAppEngineHttpRequest()->getHttpMethod());
        $this->assertEquals("/external/google/task", $task->getAppEngineHttpRequest()->getRelativeUri());
        $this->assertEquals(json_encode([
            "taskIdentifier" => "kinisite-test",
            "description" => "Test Oxford Cyber Test",
            "configuration" => ["myparam" => "tester"]
        ]), $task->getAppEngineHttpRequest()->getBody());
        $this->assertEquals(date("d/m/Y"), $task->getScheduleTime()->toDateTime()->format("d/m/Y"));


        $scheduleTime = new \DateTime();
        $scheduleTime->add(new \DateInterval("P20D"));

        $identifier = $this->queuedTaskProcessor->queueTask("kinisite-test", "kinisite-test", "Test Oxford Cyber Test", ["myparam" => "tester"], $scheduleTime);

        $task = $this->cloudTasksClient->getTask($identifier, ["responseView" => View::FULL]);
        $this->assertEquals($identifier, $task->getName());
        $this->assertEquals(HttpMethod::POST, $task->getAppEngineHttpRequest()->getHttpMethod());
        $this->assertEquals("/external/google/task", $task->getAppEngineHttpRequest()->getRelativeUri());
        $this->assertEquals(json_encode([
            "taskIdentifier" => "kinisite-test",
            "description" => "Test Oxford Cyber Test",
            "configuration" => ["myparam" => "tester"]
        ]), $task->getAppEngineHttpRequest()->getBody());
        $this->assertEquals($scheduleTime->format("d/m/Y H:i:s"), $task->getScheduleTime()->toDateTime()->setTimezone(new \DateTimeZone("Europe/London"))->format("d/m/Y H:i:s"));

    }


    public function testCanListTasks() {

        $identifier1 = $this->queuedTaskProcessor->queueTask("kinisite-test", "kinisite-test", "Test Oxford Cyber Test 1", ["myparam" => "tester"]);
        $identifier2 = $this->queuedTaskProcessor->queueTask("kinisite-test", "kinisite-test", "Test Oxford Cyber Test 2", ["myparam" => "tester"]);
        $identifier3 = $this->queuedTaskProcessor->queueTask("kinisite-test", "kinisite-test", "Test Oxford Cyber Test 3", ["myparam" => "tester"]);

        $tasks = $this->queuedTaskProcessor->listQueuedTasks("kinisite-test");
        $this->assertEquals(3, sizeof($tasks));

//        $this->assertTrue(in_array(new QueueItem("kinisite-test", $identifier3, "kinisite-test", "Test Oxford Cyber Test 3", $tasks[0]->getQueuedTime(), QueueItem::STATUS_PENDING, ["myparam" => "tester"]), $tasks));
//        $this->assertTrue(in_array(new QueueItem("kinisite-test", $identifier2, "kinisite-test", "Test Oxford Cyber Test 2", $tasks[1]->getQueuedTime(), QueueItem::STATUS_PENDING, ["myparam" => "tester"]), $tasks));
//        $this->assertTrue(in_array(new QueueItem("kinisite-test", $identifier1, "kinisite-test", "Test Oxford Cyber Test 1", $tasks[2]->getQueuedTime(), QueueItem::STATUS_PENDING, ["myparam" => "tester"]), $tasks));

        $this->assertTrue($tasks[0]->getQueuedTime() instanceof \DateTime);

    }


    public function testCanGetSingleTask() {

        $identifier1 = $this->queuedTaskProcessor->queueTask("kinisite-test", "kinisite-test", "Test Oxford Cyber Test 1", ["myparam" => "tester"]);

        $task = $this->queuedTaskProcessor->getTask("kinisite-test", $identifier1);

        $this->assertEquals(new QueueItem("kinisite-test", $identifier1, "kinisite-test", "Test Oxford Cyber Test 1", $task->getQueuedTime(), QueueItem::STATUS_PENDING, ["myparam" => "tester"],
            $task->getStartTime()), $task);

    }


    public function testCanDequeueTask() {

        // Queue task
        $identifier = $this->queuedTaskProcessor->queueTask("kinisite-test", "kinisite-test", "Test Oxford Cyber Test", ["myparam" => "tester"]);

        // Dequeue task
        $this->queuedTaskProcessor->deQueueTask("kinisite-test", $identifier);

        // Check it doesn't exists.
        try {
            $this->cloudTasksClient->getTask($identifier, ["responseView" => View::FULL]);
            $this->fail("Should have thrown here");
        } catch (ApiException $e) {
            // Success
        }

        $this->assertTrue(true);

    }
}
