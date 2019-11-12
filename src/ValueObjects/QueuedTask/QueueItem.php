<?php

namespace Kiniauth\ValueObjects\QueuedTask;

/**
 * Queue item
 *
 */
class QueueItem {


    /**
     * Queue name
     *
     * @var string
     */
    private $queueName;


    /**
     * Identifier for this queue item
     *
     * @var string
     */
    private $identifier;

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
     * @var string
     */
    private $status;

    /**
     * @var string[string]
     */
    private $configuration;


    // Status constants
    const STATUS_PENDING = "PENDING";
    const STATUS_RUNNING = "RUNNING";
    const STATUS_COMPLETED = "COMPLETED";

    /**
     * QueueItem constructor.
     * @param string $queueName
     * @param string $identifier
     * @param string $taskIdentifier
     * @param string $description
     * @param \DateTime $queuedTime
     * @param string $status
     */
    public function __construct($queueName, $identifier, $taskIdentifier, $description, $queuedTime, $status, $configuration = []) {
        $this->queueName = $queueName;
        $this->identifier = $identifier;
        $this->taskIdentifier = $taskIdentifier;
        $this->description = $description;
        $this->queuedTime = $queuedTime;
        $this->status = $status;
        $this->configuration = $configuration;
    }

    /**
     * @return string
     */
    public function getQueueName() {
        return $this->queueName;
    }

    /**
     * @return string
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getTaskIdentifier() {
        return $this->taskIdentifier;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return \DateTime
     */
    public function getQueuedTime() {
        return $this->queuedTime;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getConfiguration() {
        return $this->configuration;
    }


}
