<?php

namespace Kiniauth\Test\Services\Workflow\Task\Scheduled\Processor;

use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskInterceptor;
use Kiniauth\Services\Workflow\Task\Task;

class DateUpdatingProcessorTask implements Task {

    public function run($configuration) {

        ScheduledTaskInterceptor::$disabled = true;
        $task = ScheduledTask::fetch($configuration["id"]);
        $task->setNextStartTime(date_create_from_format("Y-m-d H:i:s", $configuration["date"]));
        $task->save();
        ScheduledTaskInterceptor::$disabled = false;

    }
}