<?php

namespace Kiniauth\Controllers\Admin;

use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;

class ScheduledTask {

    /**
     * @var ScheduledTaskService
     */
    private $scheduledTaskService;

    public function __construct($scheduledTaskService) {
        $this->scheduledTaskService = $scheduledTaskService;
    }

    /**
     * @http GET /list
     *
     * @return ScheduledTaskSummary[]
     */
    public function listScheduledTasks(): array {
        return $this->scheduledTaskService->listScheduledTasks();
    }

    /**
     * @http GET /$taskId
     *
     * @param int $taskId
     * @return ScheduledTaskSummary
     */
    public function getScheduledTask(int $taskId) {
        return $this->scheduledTaskService->getScheduledTask($taskId);
    }

    /**
     * @http PATCH /$taskId
     *
     * @param int $taskId
     * @return void
     */
    public function triggerScheduledTask(int $taskId): void {
        $this->scheduledTaskService->triggerScheduledTask($taskId);
    }

    /**
     * @http PATCH /kill/$taskId
     *
     * @param int $taskId
     * @return void
     */
    public function killScheduledTask(int $taskId): void {
        $this->scheduledTaskService->killScheduledTask($taskId);
    }

}