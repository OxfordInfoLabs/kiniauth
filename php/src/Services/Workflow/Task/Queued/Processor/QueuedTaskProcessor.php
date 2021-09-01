<?php

namespace Kiniauth\Services\Workflow\Task\Queued\Processor;

use Kiniauth\ValueObjects\QueuedTask\QueueItem;

/**
 * Interface for a queued task processor.
 *
 * Interface QueuedTaskProcessor
 *
 * @implementationConfigParam queuedtask.processor
 * @implementation default \Kiniauth\Services\Workflow\Task\Queued\Processor\DefaultQueuedTaskProcessor
 *
 * @defaultImplementation \Kiniauth\Services\Workflow\Task\Queued\Processor\DefaultQueuedTaskProcessor
 */
interface QueuedTaskProcessor {

    /**
     * Queue a task for a specific queue using the specified task identifier and
     * optional configuration.  Returns a string instance identifier
     * for this task if successful or should throw on failures.
     *
     * @param string $identifier
     * @param string $taskIdentifier
     * @param string $description
     * @param string[string] $configuration
     * @param \DateTime $startTime
     *
     * @parm return string
     */
    public function queueTask($queueName, $taskIdentifier, $description, $configuration = [], $startTime = null);


    /**
     * Get a single task by queue name and task instance identifier.
     *
     * @param $queueName
     * @param $taskInstanceIdentifier
     * @return QueueItem
     */
    public function getTask($queueName, $taskInstanceIdentifier);


    /**
     * De-queue a task using the task instance identifier
     *
     * @param $taskInstanceIdentifier
     *
     */
    public function deQueueTask($queueName, $taskInstanceIdentifier);


    /**
     * List all queued tasks for a queue.
     *
     * @param string $queueName
     * @return QueueItem[]
     */
    public function listQueuedTasks($queueName);


    /**
     * Register task status change
     *
     * @param string $queueName
     * @param string $taskInstanceIdentifier
     * @param string $status
     */
    public function registerTaskStatusChange($queueName, $taskInstanceIdentifier, $status);


}
