<?php


namespace Kiniauth\Objects\Workflow\Task\Scheduled;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Scheduled task - stored in database ready to be triggered using the associated
 * time periods to define the schedule.
 *
 * Class ScheduledTask
 * @package Kiniauth\Objects\Workflow\Task\Scheduled
 *
 */
class ScheduledTaskSummary extends ActiveRecord {

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $taskIdentifier;


    /**
     * Description for this scheduled item
     *
     * @var string
     */
    protected $description;


    /**
     * Configuration as key/value pairs which should be
     * compatible with the task identified for this item.
     *
     * @var mixed
     * @json
     */
    protected $configuration;


    /**
     * @var ScheduledTaskTimePeriod[]
     * @oneToMany
     * @childJoinColumns scheduled_task_id
     */
    protected $timePeriods;


    /**
     * Current status of this task
     *
     * @var string
     */
    protected $status = self::STATUS_PENDING;


    /**
     * @var \DateTime
     */
    protected $lastStartTime;

    /**
     * @var \DateTime
     */
    protected $lastEndTime;


    /**
     * @var \DateTime
     */
    protected $nextStartTime;


    // Status constants
    const STATUS_PENDING = "PENDING";
    const STATUS_RUNNING = "RUNNING";
    const STATUS_COMPLETED = "COMPLETED";
    const STATUS_FAILED = "FAILED";

    /**
     * ScheduledTaskSummary constructor.
     *
     * @param string $taskIdentifier
     * @param string $description
     * @param mixed $configuration
     * @param ScheduledTaskTimePeriod[] $timePeriods
     * @param string $status
     * @param \DateTime $lastStartTime
     * @param \DateTime $lastEndTime
     * @param integer $id
     */
    public function __construct($taskIdentifier, $description, $configuration, $timePeriods, $status = self::STATUS_PENDING,
                                $nextStartTime = null,
                                $lastStartTime = null, $lastEndTime = null, $id = null) {
        $this->taskIdentifier = $taskIdentifier;
        $this->description = $description;
        $this->configuration = $configuration;
        $this->timePeriods = $timePeriods;
        $this->status = $status;
        $this->nextStartTime = $nextStartTime;
        $this->lastStartTime = $lastStartTime;
        $this->lastEndTime = $lastEndTime;
        $this->id = $id;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTaskIdentifier() {
        return $this->taskIdentifier;
    }

    /**
     * @param string $taskIdentifier
     */
    public function setTaskIdentifier($taskIdentifier) {
        $this->taskIdentifier = $taskIdentifier;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getConfiguration() {
        return $this->configuration;
    }

    /**
     * @param mixed $configuration
     */
    public function setConfiguration($configuration) {
        $this->configuration = $configuration;
    }

    /**
     * @return ScheduledTaskTimePeriod[]
     */
    public function getTimePeriods() {
        return $this->timePeriods;
    }

    /**
     * @param ScheduledTaskTimePeriod[] $timePeriods
     */
    public function setTimePeriods($timePeriods) {
        $this->timePeriods = $timePeriods;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     * @return \DateTime
     */
    public function getLastStartTime() {
        return $this->lastStartTime;
    }

    /**
     * @param \DateTime $lastStartTime
     */
    public function setLastStartTime($lastStartTime) {
        $this->lastStartTime = $lastStartTime;
    }

    /**
     * @return \DateTime
     */
    public function getLastEndTime() {
        return $this->lastEndTime;
    }

    /**
     * @param \DateTime $lastEndTime
     */
    public function setLastEndTime($lastEndTime) {
        $this->lastEndTime = $lastEndTime;
    }

    /**
     * @return \DateTime
     */
    public function getNextStartTime() {
        return $this->nextStartTime;
    }

    /**
     * @param \DateTime $nextStartTime
     */
    public function setNextStartTime($nextStartTime) {
        $this->nextStartTime = $nextStartTime;
    }


}