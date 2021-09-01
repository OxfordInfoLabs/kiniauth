<?php


namespace Kiniauth\Services\Workflow\Task\Scheduled\Processor;


use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskLog;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Services\Workflow\Task\Task;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

/**
 * Class ScheduledTaskProcessor
 *
 * @implementation default Kiniauth\Services\Workflow\Task\Scheduled\Processor\DefaultScheduledTaskProcessor
 * @defaultImplementation Kiniauth\Services\Workflow\Task\Scheduled\Processor\DefaultScheduledTaskProcessor
 */
abstract class ScheduledTaskProcessor {

    /**
     * Process an array of scheduled tasks
     *
     * @param ScheduledTask[] $scheduledTasks
     */
    public abstract function processScheduledTasks($scheduledTasks);


    /**
     * Process a single scheduled task - updates statuses on completion and failure etc.
     *
     * @param ScheduledTask $scheduledTask
     */
    public function processScheduledTask($scheduledTask) {


        $output = null;
        try {

            // Refresh task to check for other overlapping runs
            if ($scheduledTask->getId())
                $scheduledTask = ScheduledTask::fetch($scheduledTask->getId());

            // Return if we are already in a running state
            if ($scheduledTask->getStatus() == ScheduledTask::STATUS_RUNNING)
                return;

            // Grab the task from the container
            $task = Container::instance()->getInterfaceImplementation(Task::class, $scheduledTask->getTaskIdentifier());

            // Ensure we record the last start time and set status to running
            $scheduledTask->setLastStartTime(new \DateTime());
            $scheduledTask->setStatus(ScheduledTask::STATUS_RUNNING);
            $scheduledTask->save();

            // Run the task
            $output = $task->run($scheduledTask->getConfiguration());

            // Update status to completed
            $scheduledTask->setStatus(ScheduledTask::STATUS_COMPLETED);


        } catch (\Exception $e) {
            $output = $e->getMessage();
            $scheduledTask->setStatus(ScheduledTask::STATUS_FAILED);
        }

        // Save the scheduled task at the end
        $scheduledTask->setLastEndTime(new \DateTime());
        $scheduledTask->recalculateNextStartTime();
        $scheduledTask->save();

        // Create a log entry as well
        $logEntry = new ScheduledTaskLog($scheduledTask->getId(), $scheduledTask->getLastStartTime(), $scheduledTask->getLastEndTime(),
            $scheduledTask->getStatus(), $output);

        $logEntry->save();

    }

}