<?php


namespace Kiniauth\Services\Workflow\Task\Scheduled\Processor;

use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;

/**
 * Default scheduled task processor.  This simply executes each task in sequence in a loop.
 *
 * Class DefaultScheduledTaskProcessor
 * @package Kiniauth\Test\Services\Workflow\Task\Scheduled\Processor
 */
class DefaultScheduledTaskProcessor extends ScheduledTaskProcessor {

    /**
     * Process all scheduled tasks in sequence
     *
     * @param ScheduledTask[] $scheduledTasks
     */
    public function processScheduledTasks($scheduledTasks) {

        foreach ($scheduledTasks as $scheduledTaskSummary) {
            $this->processScheduledTask($scheduledTaskSummary);
        }

    }
}