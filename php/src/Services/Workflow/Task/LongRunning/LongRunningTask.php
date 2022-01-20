<?php


namespace Kiniauth\Services\Workflow\Task\LongRunning;


use Kiniauth\Objects\Workflow\Task\LongRunning\StoredLongRunningTask;

abstract class LongRunningTask {

    /**
     * @var StoredLongRunningTask
     */
    private $storedLongRunningTask;


    /**
     * Start a long running task
     *
     * @return mixed
     */
    public function start($storedLongRunningTask) {
        $this->storedLongRunningTask = $storedLongRunningTask;
        return $this->run();
    }


    /**
     * Update progress data for the long running task
     *
     * @param $progressData
     */
    public function updateProgress($progressData) {
        $this->storedLongRunningTask->setProgressData($progressData);
        $this->storedLongRunningTask->save();
    }


    /**
     * Run a long running task.  Should execute logic and either return
     * a result or throw an exception to indicate failure
     *
     * @return mixed
     */
    public abstract function run();
}