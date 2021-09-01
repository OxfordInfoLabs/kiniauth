<?php

namespace Kiniauth\Objects\Workflow\Task\Queued;

use Kiniauth\Services\Workflow\QueuedTask\QueuedTask;
use Kiniauth\ValueObjects\QueuedTask\QueueItem;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Stored queue item.
 *
 * @table ka_queue
 * @generate
 */
class StoredQueueItem extends ActiveRecord {

    /**
     * @var integer
     */
    private $id;

    /**
     * Queue name
     *
     * @var string
     */
    private $queueName;


    /**
     * Task identifier for this queue item.
     *
     * @var string
     */
    private $taskIdentifier;


    /**
     * Description for this queue item.
     *
     * @var string
     */
    private $description;


    /**
     * @var \DateTime
     */
    private $queuedTime;


    /**
     * @var string[string]
     * @json
     */
    private $configuration;


    /**
     * @var \DateTime
     */
    private $startTime;

    /**
     * @var string
     */
    private $status = QueueItem::STATUS_PENDING;

    /**
     * StoredQueueItem constructor.
     * @param string $queueName
     * @param string $taskIdentifier
     * @param string $description
     * @param string $configuration
     * @param \DateTime $startTime
     */
    public function __construct($queueName, $taskIdentifier, $description, $configuration, $startTime) {
        $this->queueName = $queueName;
        $this->taskIdentifier = $taskIdentifier;
        $this->description = $description;
        $this->configuration = $configuration;
        $this->startTime = $startTime;
        $this->queuedTime = new \DateTime();
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
    public function getQueueName() {
        return $this->queueName;
    }

    /**
     * @param string $queueName
     */
    public function setQueueName($queueName) {
        $this->queueName = $queueName;
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
     * @return \DateTime
     */
    public function getQueuedTime() {
        return $this->queuedTime;
    }

    /**
     * @param \DateTime $queuedTime
     */
    public function setQueuedTime($queuedTime) {
        $this->queuedTime = $queuedTime;
    }

    /**
     * @return string
     */
    public function getConfiguration() {
        return $this->configuration;
    }

    /**
     * @param string $configuration
     */
    public function setConfiguration($configuration) {
        $this->configuration = $configuration;
    }

    /**
     * @return \DateTime
     */
    public function getStartTime() {
        return $this->startTime;
    }

    /**
     * @param \DateTime $startTime
     */
    public function setStartTime($startTime) {
        $this->startTime = $startTime;
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


}
