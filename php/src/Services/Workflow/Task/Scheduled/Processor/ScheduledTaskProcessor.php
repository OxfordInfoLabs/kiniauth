<?php


namespace Kiniauth\Services\Workflow\Task\Scheduled\Processor;


use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskLog;
use Kiniauth\Services\Workflow\Task\Task;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\DebugException;
use Kinikit\Core\Util\TaskManager;

/**
 * Class ScheduledTaskProcessor
 *
 * @implementation default \Kiniauth\Services\Workflow\Task\Scheduled\Processor\DefaultScheduledTaskProcessor
 *
 * @defaultImplementation \Kiniauth\Services\Workflow\Task\Scheduled\Processor\DefaultScheduledTaskProcessor
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
            if (($scheduledTask->getStatus() == ScheduledTask::STATUS_RUNNING) ||
                ($scheduledTask->getNextStartTime() > new \DateTime()))
                return $scheduledTask;

            // Kill if requested
            if ($scheduledTask->getStatus() === ScheduledTask::STATUS_KILLING || !is_null($scheduledTask->getPid())) {
                $this->killScheduledTask($scheduledTask);
                $output = 'Task Killed.';
            } // Otherwise run
            else {
                $output = $this->runScheduledTask($scheduledTask);
            }

        } catch (\Exception $e) {
            $output = $e instanceof DebugException ? $e->returnDebugMessage() : $e->getMessage();
            $scheduledTask->setStatus(ScheduledTask::STATUS_FAILED);
            $scheduledTask->setPid(null);
        }

        // Save the scheduled task at the end
        $scheduledTask->setLastEndTime(new \DateTime());
        $scheduledTask->save();

        // Create a log entry as well
        $logEntry = new ScheduledTaskLog($scheduledTask->getId(), $scheduledTask->getLastStartTime(), $scheduledTask->getLastEndTime(),
            $scheduledTask->getStatus(), $output);

        $logEntry->save();

        return $scheduledTask;

    }

    private function runScheduledTask($scheduledTask) {

        // Grab the task from the container
        $task = Container::instance()->getInterfaceImplementation(Task::class, $scheduledTask->getTaskIdentifier());

        // Ensure we record the last start time and set status to running
        $scheduledTask->setLastStartTime(new \DateTime());
        $scheduledTask->setStatus(ScheduledTask::STATUS_RUNNING);

        $timeoutTime = (new \DateTime())->add(new \DateInterval("PT{$scheduledTask->getTimeoutSeconds()}S"));
        $scheduledTask->setTimeoutTime($timeoutTime);

        $scheduledTask->setPid(TaskManager::getProcessId());

        $scheduledTask->save();

        // Run the task
        $output = $task->run($scheduledTask->getConfiguration());

        // Update status to completed and clear the PID
        $scheduledTask->setStatus(ScheduledTask::STATUS_COMPLETED);
        $scheduledTask->setPid(null);

        return $output;

    }

    private function killScheduledTask($scheduledTask): void {

        $pid = $scheduledTask->getPid();

        $taskManager = Container::instance()->get(TaskManager::class);
        $taskManager->killProcess($pid);

        $scheduledTask->setStatus(ScheduledTask::STATUS_KILLED);
        $scheduledTask->setPid(null);

    }

}