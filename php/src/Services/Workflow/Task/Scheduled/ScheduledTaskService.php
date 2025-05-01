<?php


namespace Kiniauth\Services\Workflow\Task\Scheduled;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskInterceptor;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskLog;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Services\Workflow\Task\Scheduled\Processor\ScheduledTaskProcessor;

class ScheduledTaskService {

    /**
     * @var ScheduledTaskProcessor
     */
    private $scheduledTaskProcessor;

    /**
     * ScheduledTaskService constructor.
     *
     * @param ScheduledTaskProcessor $scheduledTaskProcessor
     */
    public function __construct($scheduledTaskProcessor) {
        $this->scheduledTaskProcessor = $scheduledTaskProcessor;
    }


    /**
     * Save scheduled task
     *
     * @param ScheduledTaskSummary $scheduledTaskSummary
     * @param string $projectkey
     * @param integer $accountId
     */
    public function saveScheduledTask($scheduledTaskSummary, $projectkey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $task = new ScheduledTask($scheduledTaskSummary, $projectkey, $accountId);
        $task->save();
        return $task->getId();
    }


    /**
     * Trigger a scheduled task immediately, overriding the current schedule.
     * The current schedule will be resumed after the task has run.
     *
     * @param $taskId
     * @return void
     */
    public function triggerScheduledTask($taskId) {

        // Grab the scheduled task and reset the time and status
        $task = ScheduledTask::fetch($taskId);
        $task->setNextStartTime(new \DateTime());

        // If we are not in a running state, reset to pending so
        // the task gets picked up
        if ($task->getStatus() !== ScheduledTask::STATUS_RUNNING)
            $task->setStatus(ScheduledTask::STATUS_PENDING);

        // Suppress interceptor on save to avoid the time being immediately reset
        $preDisabled = ScheduledTaskInterceptor::$disabled;
        ScheduledTaskInterceptor::$disabled = true;
        $task->save();
        ScheduledTaskInterceptor::$disabled = $preDisabled;
    }

    /**
     * Set a task to be pending kill
     *
     * @param $taskId
     * @return void
     */
    public function killScheduledTask($taskId) {

        // Grab the scheduled task
        /** @var ScheduledTask $task */
        $task = ScheduledTask::fetch($taskId);

        // Don't do anything if task is not running (or has no PID)
        if ($task->getStatus() !== ScheduledTaskSummary::STATUS_RUNNING || is_null($task->getPid())) {
            return;
        }

        $task->setStatus(ScheduledTaskSummary::STATUS_KILLING);

        $preDisabled = ScheduledTaskInterceptor::$disabled;
        ScheduledTaskInterceptor::$disabled = true;
        $task->save();
        ScheduledTaskInterceptor::$disabled = $preDisabled;

    }

    /**
     * List all scheduled tasks
     *
     * @return array
     */
    public function listScheduledTasks(): array {
        return ScheduledTask::filter();
    }

    /**
     * Get a scheduled task by id
     *
     * @param $taskId
     * @return ScheduledTaskSummary
     */
    public function getScheduledTask($taskId) {
        $task = ScheduledTask::fetch($taskId);
        return $task->returnSummary();
    }


    /**
     * Delete a scheduled task by id
     *
     * @param $taskId
     */
    public function deleteScheduledTask($taskId) {
        $task = ScheduledTask::fetch($taskId);
        $task->remove();
    }


    /**
     * Process all due tasks according to the schedule information
     */
    public function processDueTasks($taskGroup = null) {

        // Process any timed out tasks
        if ($taskGroup) {
            $timedOutTasks = ScheduledTask::filter("WHERE timeoutTime <= ? AND status LIKE ? AND task_group = ?",
                date('Y-m-d H:i:s'), ScheduledTask::STATUS_RUNNING, $taskGroup);
        } else {
            $timedOutTasks = ScheduledTask::filter("WHERE timeoutTime <= ? AND status LIKE ?",
                date('Y-m-d H:i:s'), ScheduledTask::STATUS_RUNNING);
        }

        if (sizeof($timedOutTasks)) {
            foreach ($timedOutTasks as $task) {
                $task->setStatus(ScheduledTaskSummary::STATUS_TIMED_OUT);
                $task->save();

                $logEntry = new ScheduledTaskLog($task->getId(), $task->getLastStartTime(), $task->getLastEndTime(),
                    $task->getStatus(), "Timed Out");
                $logEntry->save();
            }
        }


        // Gather due tasks
        if ($taskGroup) {
            $dueTasks = ScheduledTask::filter("WHERE nextStartTime <= ? AND (status IS NULL OR status <> ?) AND taskGroup = ?",
                date('Y-m-d H:i:s'), ScheduledTask::STATUS_RUNNING, $taskGroup);
        } else {
            $dueTasks = ScheduledTask::filter("WHERE nextStartTime <= ? AND (status IS NULL OR status <> ?)",
                date('Y-m-d H:i:s'), ScheduledTask::STATUS_RUNNING);
        }

        if (sizeof($dueTasks))
            $this->scheduledTaskProcessor->processScheduledTasks($dueTasks);

    }

}