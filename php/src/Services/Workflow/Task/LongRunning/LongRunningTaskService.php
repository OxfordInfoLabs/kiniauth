<?php


namespace Kiniauth\Services\Workflow\Task\LongRunning;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Workflow\Task\LongRunning\StoredLongRunningTask;
use Kiniauth\Objects\Workflow\Task\LongRunning\StoredLongRunningTaskSummary;
use Kiniauth\Services\Workflow\Task\Task;
use Kinikit\Core\Logging\Logger;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

/**
 * Long running task service.  Manages the lifecycle of a long running task
 *
 * Class LongRunningTaskService
 * @package Kiniauth\Services\Workflow\Task\LongRunning
 */
class LongRunningTaskService {

    /**
     * Start a long running task.  A task identifier is required as this allows for logical
     * categorisation of tasks.
     *
     * The passed task will be started immediately and tracked.
     *
     * @param string $taskIdentifier
     * @param LongRunningTask $task
     * @param string $taskKey
     *
     * @return mixed
     */
    public function startTask($taskIdentifier, $task, $taskKey = null, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT,
                              $expiryMinutes = StoredLongRunningTask::DEFAULT_EXPIRY_MINUTES, $timeoutSeconds = StoredLongRunningTask::DEFAULT_TIMEOUT_SECONDS) {

        // Create a store task
        $storedLongRunningTask = new StoredLongRunningTask($taskIdentifier, $taskKey, $expiryMinutes, $timeoutSeconds, $projectKey, $accountId);
        $storedLongRunningTask->save();

        $exception = null;
        $result = null;
        try {
            // Actually start the task
            $result = $task->start($storedLongRunningTask);
            
            // Register status
            $storedLongRunningTask->setStatus(StoredLongRunningTask::STATUS_COMPLETED);

            // Set result
            $storedLongRunningTask->setResult($result);

        } catch (\Exception $e) {

            // Register status
            $storedLongRunningTask->setStatus(StoredLongRunningTask::STATUS_FAILED);

            // Set result to exception message
            $storedLongRunningTask->setResult($e->getMessage());

            $exception = $e;

        }


        // Register finished and expiry dates
        $now = new \DateTime();
        $storedLongRunningTask->setFinishedDate(clone new \DateTime());
        $now->add(new \DateInterval("PT" . $expiryMinutes . "M"));
        $storedLongRunningTask->setExpiryDate($now);
        
        $storedLongRunningTask->save();

        if ($exception)
            throw $exception;

        else
            return $result;

    }


    /**
     * Get a long running task by task key.  This is useful for periodically checking
     * on the progress of a task and for determining completion.
     *
     * @param string $taskKey
     * @return StoredLongRunningTaskSummary
     */
    public function getStoredTaskByTaskKey($taskKey) {
        $task = StoredLongRunningTask::filter("WHERE task_key = ?", $taskKey);
        if (sizeof($task) > 0) {
            return $task[0];
        } else {
            throw new ObjectNotFoundException(StoredLongRunningTaskSummary::class, $taskKey);
        }
    }


    /**
     * Process any stored tasks which may have timed out
     *
     * @objectInterceptorDisabled
     */
    public function processTimeouts() {

        $timedOuts = StoredLongRunningTask::filter("WHERE timeoutDate <= ?", date("Y-m-d H:i:s"));

        foreach ($timedOuts as $timedOut) {

            $timedOut->setStatus(StoredLongRunningTask::STATUS_TIMEOUT);

            // Register finished and expiry dates
            $now = new \DateTime();
            $timedOut->setFinishedDate(clone new \DateTime());
            $now->add(new \DateInterval("PT" . $timedOut->getExpiryMinutes() . "M"));
            $timedOut->setExpiryDate($now);

            // Save timed out
            $timedOut->save();
        }


    }


    /**
     * Process any stored tasks which have expired
     *
     * @objectInterceptorDisabled
     */
    public function processExpiries() {

        $expiries = StoredLongRunningTask::filter("WHERE expiryDate <= ?", date("Y-m-d H:i:s"));

        foreach ($expiries as $expired) {
            $expired->remove();
        }

    }

}