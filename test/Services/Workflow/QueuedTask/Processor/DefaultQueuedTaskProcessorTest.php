<?php

namespace Kiniauth\Services\Workflow\QueuedTask\Processor;

use Kiniauth\Objects\Workflow\QueuedTask\StoredQueueItem;
use Kiniauth\Test\TestBase;
use Kiniauth\ValueObjects\QueuedTask\QueueItem;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

class DefaultQueuedTaskProcessorTest extends TestBase {

    /**
     * @var QueuedTaskProcessor
     */
    private $defaultQueuedTaskProcessor;

    public function setUp(): void {

        $this->defaultQueuedTaskProcessor = Container::instance()->get(QueuedTaskProcessor::class);
    }


    public function testCanQueueTasksAndTheyAreStoredAsStoredQueueObjects() {

        $this->assertTrue($this->defaultQueuedTaskProcessor instanceof DefaultQueuedTaskProcessor);

        $identifier = $this->defaultQueuedTaskProcessor->queueTask("testqueue", "test", "New Queued Item", ["id" => 256]);

        $object = StoredQueueItem::fetch($identifier);
        $this->assertEquals("testqueue", $object->getQueueName());
        $this->assertEquals("test", $object->getTaskIdentifier());
        $this->assertEquals("New Queued Item", $object->getDescription());
        $this->assertEquals(["id" => 256], $object->getConfiguration());
        $this->assertEquals(QueueItem::STATUS_PENDING, $object->getStatus());
        $this->assertEquals(date("d/m/Y"), $object->getQueuedTime()->format("d/m/Y"));
        $this->assertNull($object->getStartTime());


        $identifier = $this->defaultQueuedTaskProcessor->queueTask("testqueue", "test", "New Queued Item", ["id" => 257], date_create_from_format("d/m/Y H:i:s", "01/01/2030 01:00:00"));

        $object = StoredQueueItem::fetch($identifier);
        $this->assertEquals("testqueue", $object->getQueueName());
        $this->assertEquals("test", $object->getTaskIdentifier());
        $this->assertEquals("New Queued Item", $object->getDescription());
        $this->assertEquals(["id" => 257], $object->getConfiguration());
        $this->assertEquals(QueueItem::STATUS_PENDING, $object->getStatus());
        $this->assertEquals(date("d/m/Y"), $object->getQueuedTime()->format("d/m/Y"));
        $this->assertEquals("01/01/2030 01:00:00", $object->getStartTime()->format("d/m/Y H:i:s"));


    }


    public function testCanGetTasksByReturnedId() {

        $identifier = $this->defaultQueuedTaskProcessor->queueTask("testqueue", "test", "New Queued Item", ["id" => 256]);

        $retrievedTask = $this->defaultQueuedTaskProcessor->getTask("testqueue", $identifier);
        $reObject = new QueueItem("testqueue", $identifier, "test", "New Queued Item", $retrievedTask->getQueuedTime(), QueueItem::STATUS_PENDING, ["id" => 256]);

        $this->assertEquals($reObject, $retrievedTask);

    }


    public function testCanDeQueueTasksIfTheyArePending() {

        $identifier = $this->defaultQueuedTaskProcessor->queueTask("testqueue", "test", "New Queued Item", ["id" => 256]);

        $identifier2 = $this->defaultQueuedTaskProcessor->queueTask("testqueue", "test", "New Queued Item", ["id" => 256]);
        $object = StoredQueueItem::fetch($identifier2);
        $object->setStatus(QueueItem::STATUS_RUNNING);
        $object->save();

        // Attempt to de-queue items
        $this->defaultQueuedTaskProcessor->deQueueTask("testqueue", $identifier);
        $this->defaultQueuedTaskProcessor->deQueueTask("testqueue", $identifier2);

        try {
            StoredQueueItem::fetch($identifier);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // Success
        }

        // This should be fine
        $object = StoredQueueItem::fetch($identifier2);
        $this->assertTrue($object instanceof StoredQueueItem);
    }


    public function testCanListTasksForQueue() {

        $item1 = $this->defaultQueuedTaskProcessor->queueTask("testqueue2", "test", "New Queued Item 1", ["id" => 256]);
        $item2 = $this->defaultQueuedTaskProcessor->queueTask("testqueue3", "test", "New Queued Item 2", ["id" => 256]);
        $item3 = $this->defaultQueuedTaskProcessor->queueTask("testqueue3", "test", "New Queued Item 3", ["id" => 256]);

        $queueItems = $this->defaultQueuedTaskProcessor->listQueuedTasks("testqueue2");
        $this->assertEquals([new QueueItem("testqueue2", $item1, "test", "New Queued Item 1", $queueItems[0]->getQueuedTime(), QueueItem::STATUS_PENDING, ["id" => 256])], $queueItems);

        $queueItems = $this->defaultQueuedTaskProcessor->listQueuedTasks("testqueue3");
        $this->assertEquals([
            new QueueItem("testqueue3", $item2, "test", "New Queued Item 2", $queueItems[0]->getQueuedTime(), QueueItem::STATUS_PENDING, ["id" => 256]),
            new QueueItem("testqueue3", $item3, "test", "New Queued Item 3", $queueItems[1]->getQueuedTime(), QueueItem::STATUS_PENDING, ["id" => 256])
        ], $queueItems);


    }


    public function testCanRegisterStatusChangeForQueueItem() {

        $item1 = $this->defaultQueuedTaskProcessor->queueTask("testqueue2", "test", "New Queued Item 1", ["id" => 256]);

        $reItem = StoredQueueItem::fetch($item1);
        $this->assertEquals(QueueItem::STATUS_PENDING, $reItem->getStatus());

        $this->defaultQueuedTaskProcessor->registerTaskStatusChange("testqueue2", $item1, QueueItem::STATUS_RUNNING);

        $reItem = StoredQueueItem::fetch($item1);
        $this->assertEquals(QueueItem::STATUS_RUNNING, $reItem->getStatus());

        $this->defaultQueuedTaskProcessor->registerTaskStatusChange("testqueue2", $item1, QueueItem::STATUS_PENDING);

        $reItem = StoredQueueItem::fetch($item1);
        $this->assertEquals(QueueItem::STATUS_PENDING, $reItem->getStatus());


    }

}
