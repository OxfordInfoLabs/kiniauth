<?php


namespace Kiniauth\Objects\Workflow\Task\Scheduled;


use Kinikit\Core\Configuration\Configuration;
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
     * @var string
     */
    protected $lastStartTime;

    /**
     * @var string
     */
    protected $lastEndTime;


    /**
     * @var string
     */
    protected $nextStartTime;

    protected int $timeoutSeconds;

    /**
     * @var string
     */
    protected $timeoutTime;

    /**
     * @var string
     */
    protected $taskGroup;

    /**
     * @var int
     */
    protected $pid;


    // Status constants
    const STATUS_PENDING = "PENDING";
    const STATUS_RUNNING = "RUNNING";
    const STATUS_COMPLETED = "COMPLETED";
    const STATUS_FAILED = "FAILED";
    const STATUS_KILLING = "KILLING";
    const STATUS_KILLED = "KILLED";
    const STATUS_TIMED_OUT = "TIMED_OUT";

    /**
     * ScheduledTaskSummary constructor.
     *
     * @param string $taskIdentifier
     * @param string $description
     * @param mixed $configuration
     * @param ScheduledTaskTimePeriod[] $timePeriods
     * @param string $status
     * @param integer $id
     * @param string $nextStartTime
     * @param string $lastStartTime
     * @param string $lastEndTime
     * @param string $timeoutTime
     * @param int $timeoutSeconds
     * @param string $taskGroup
     * @param int $pid
     */
    public function __construct($taskIdentifier, $description, $configuration, $timePeriods, $status = self::STATUS_PENDING,
                                $nextStartTime = null, $lastStartTime = null, $lastEndTime = null, $timeoutTime = null, $timeoutSeconds = 86400,
                                $id = null, $taskGroup = null, $pid = null) {
        $this->taskIdentifier = $taskIdentifier;
        $this->description = $description;
        $this->configuration = $configuration;
        $this->timePeriods = $timePeriods;
        $this->status = $status;
        $this->nextStartTime = $nextStartTime;
        $this->lastStartTime = $lastStartTime;
        $this->lastEndTime = $lastEndTime;
        $this->id = $id;
        $this->timeoutTime = $timeoutTime;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->taskGroup = $taskGroup ?? Configuration::readParameter("scheduled.task.default.group");
        $this->pid = $pid;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
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
     * @return string
     */
    public function getLastStartTime() {
        return $this->lastStartTime;
    }

    /**
     * @param string $lastStartTime
     */
    public function setLastStartTime($lastStartTime) {
        $this->lastStartTime = $lastStartTime;
    }

    /**
     * @return string
     */
    public function getLastEndTime() {
        return $this->lastEndTime;
    }

    /**
     * @param string $lastEndTime
     */
    public function setLastEndTime($lastEndTime) {
        $this->lastEndTime = $lastEndTime;
    }

    /**
     * @return string
     */
    public function getNextStartTime() {
        return $this->nextStartTime;
    }

    /**
     * @param string $nextStartTime
     */
    public function setNextStartTime($nextStartTime) {
        print_r("HEY");
        $this->nextStartTime = $nextStartTime;
    }

    public function getTimeoutSeconds(): int {
        return $this->timeoutSeconds;
    }

    /**
     * @param int $timeoutSeconds
     */
    public function setTimeoutSeconds($timeoutSeconds) {
        $this->timeoutSeconds = $timeoutSeconds;
    }

    /**
     * @return string
     */
    public function getTimeoutTime() {
        return $this->timeoutTime;
    }

    /**
     * @param string $timeoutTime
     */
    public function setTimeoutTime($timeoutTime) {
        $this->timeoutTime = $timeoutTime;
    }

    /**
     * @return string
     */
    public function getTaskGroup() {
        return $this->taskGroup;
    }

    /**
     * @param $group
     */
    public function setTaskGroup($taskGroup) {
        $this->taskGroup = $taskGroup;
    }

    public function getPid() {
        return $this->pid;
    }

    public function setPid($pid): void {
        $this->pid = $pid;
    }

}