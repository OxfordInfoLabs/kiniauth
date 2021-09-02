<?php


namespace Kiniauth\Services\Workflow\Task\Scheduled;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
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
    public function processDueTasks() {

        $dueTasks = ScheduledTask::filter("WHERE nextStartTime <= ? AND (status IS NULL OR status <> ?)",
            date('Y-m-d H:i:s'), ScheduledTask::STATUS_RUNNING);

        if (sizeof($dueTasks))
            $this->scheduledTaskProcessor->processScheduledTasks($dueTasks);

    }

}