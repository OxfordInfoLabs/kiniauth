<?php


namespace Kiniauth\Objects\Workflow\Task\LongRunning;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_long_running_task
 */
class StoredLongRunningTaskSummary extends ActiveRecord {


    /**
     * Arbitrary task key assigned when task created for tracking purposes
     *
     * @var string
     * @required
     */
    protected $taskKey;
    /**
     * JSON progress data specific to the operation being run
     *
     * @var mixed
     * @sqlType LONGTEXT
     * @json
     */
    protected $progressData;
    /**
     * The time the task was started
     *
     * @var \DateTime
     */
    protected $startedDate;
    /**
     * Task identifier for categorising entries
     *
     * @var string
     * @required
     */
    protected $taskIdentifier;
    /**
     * Result data captured as a JSON block
     *
     * @var mixed
     * @sqlType LONGTEXT
     * @json
     */
    protected $result;
    /**
     * Date after which this task entry can be deleted
     *
     * @var \DateTime
     */
    protected $expiryDate;
    /**
     * Status - one of the class STATUS constants
     *
     * @var string
     * @required
     */
    protected $status;
    /**
     * The time the task ended
     *
     * @var \DateTime
     */
    protected $finishedDate;


    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getTaskKey() {
        return $this->taskKey;
    }

    /**
     * @return mixed
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * @return \DateTime
     */
    public function getFinishedDate() {
        return $this->finishedDate;
    }

    /**
     * @return mixed
     */
    public function getProgressData() {
        return $this->progressData;
    }

    /**
     * @return \DateTime
     */
    public function getExpiryDate() {
        return $this->expiryDate;
    }

    /**
     * @return \DateTime
     */
    public function getStartedDate() {
        return $this->startedDate;
    }

    /**
     * @return string
     */
    public function getTaskIdentifier() {
        return $this->taskIdentifier;
    }
}