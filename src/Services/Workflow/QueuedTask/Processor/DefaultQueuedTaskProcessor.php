<?php


namespace Kiniauth\Services\Workflow\QueuedTask\Processor;


use Kiniauth\Objects\Workflow\QueuedTask\StoredQueueItem;
use Kiniauth\ValueObjects\QueuedTask\QueueItem;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

class DefaultQueuedTaskProcessor implements QueuedTaskProcessor {

    /**
     * Queue a task for a specific queue using the specified task identifier and
     * optional configuration.  Return the numeric id generated when saving this.
     *
     * @param string $identifier
     * @param string[string] $configuration
     * @parm return string
     */
    public function queueTask($queueName, $taskIdentifier, $description, $configuration = []) {
        $storedQueueItem = new StoredQueueItem($queueName, $taskIdentifier, $description, $configuration);
        $storedQueueItem->save();
        return $storedQueueItem->getId();
    }


    /**
     * Get a single task by queue name and task instance identifier.
     *
     * @param $queueName
     * @param $taskInstanceIdentifier
     * @return mixed
     */
    public function getTask($queueName, $taskInstanceIdentifier) {
        $queueItem = StoredQueueItem::fetch($taskInstanceIdentifier);
        return new QueueItem($queueItem->getQueueName(), $queueItem->getId(),
            $queueItem->getTaskIdentifier(), $queueItem->getDescription(),
            $queueItem->getQueuedTime(), $queueItem->getStatus(), $queueItem->getConfiguration());
    }


    /**
     * De-queue a task using the task instance identifier
     *
     * @param $taskInstanceIdentifier
     * @return mixed
     */
    public function deQueueTask($queueName, $taskInstanceIdentifier) {
        try {
            $item = StoredQueueItem::fetch($taskInstanceIdentifier);
            if ($item->getStatus() != QueueItem::STATUS_RUNNING)
                $item->remove();
        } catch (ObjectNotFoundException $e) {
            // Continue
        }
    }

    /**
     * List all queued tasks for a queue.
     *
     * @param string $queueName
     * @return mixed
     */
    public function listQueuedTasks($queueName) {
        $queueItems = StoredQueueItem::filter("WHERE queue_name = ? ORDER BY id", $queueName);
        $items = [];
        foreach ($queueItems as $queueItem) {
            $items[] = new QueueItem($queueItem->getQueueName(), $queueItem->getId(),
                $queueItem->getTaskIdentifier(), $queueItem->getDescription(),
                $queueItem->getQueuedTime(), $queueItem->getStatus(), $queueItem->getConfiguration());
        }

        return $items;
    }


    /**
     * Register task status change
     *
     * @param string $queueName
     * @param string $taskInstanceIdentifier
     * @param string $status
     */
    public function registerTaskStatusChange($queueName, $taskInstanceIdentifier, $status) {
        $item = StoredQueueItem::fetch($taskInstanceIdentifier);
        $item->setStatus($status);
        $item->save();
    }
}
