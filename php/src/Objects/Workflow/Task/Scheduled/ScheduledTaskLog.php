<?php


namespace Kiniauth\Objects\Workflow\Task\Scheduled;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class ScheduledTaskLog
 * @package Kiniauth\Objects\Workflow\Task\Scheduled
 *
 * @table ka_scheduled_task_log
 * @generate
 */
class ScheduledTaskLog extends ActiveRecord {

    /**
     * @var integer
     */
    private $id;

    /**
     * Parent task id for which this log entry exists
     *
     * @var integer
     */
    private $scheduledTaskId;


    /**
     * @var \DateTime
     */
    private $startTime;


    /**
     * @var \DateTime
     */
    private $endTime;


    /**
     * @var string
     */
    private $status;


    /**
     * @var mixed
     * @json
     */
    private $logOutput;

    /**
     * ScheduledTaskLog constructor.
     * @param int $scheduledTaskId
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @param string $status
     * @param string $logOutput
     */
    public function __construct($scheduledTaskId, $startTime, $endTime, $status, $logOutput) {
        $this->scheduledTaskId = $scheduledTaskId;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->status = $status;
        $this->logOutput = $logOutput;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getScheduledTaskId() {
        return $this->scheduledTaskId;
    }

    /**
     * @return \DateTime
     */
    public function getStartTime() {
        return $this->startTime;
    }

    /**
     * @return \DateTime
     */
    public function getEndTime() {
        return $this->endTime;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getLogOutput() {
        return $this->logOutput;
    }


}