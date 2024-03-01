<?php

namespace Kiniauth\Objects\Workflow;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Log of completed stages for object workflow.
 *
 * @table ka_object_workflow_completed_step
 * @generate
 */
class ObjectWorkflowCompletedStep extends ActiveRecord {

    /**
     * @var string
     * @primaryKey
     */
    private $objectClass;

    /**
     * @var string
     * @primaryKey
     */
    private $objectPk;

    /**
     * @var string
     * @primaryKey
     */
    private $stepKey;

    /**
     * @var \DateTime
     */
    private $completedTime;


    /**
     * @var string
     */
    private $status;


    /**
     * @var string
     * @sqlType LONGTEXT
     */
    private $logOutput;


    /**
     * Optional trigger value if required for comparison
     *
     * @var string
     * @primaryKey
     */
    private $triggerValue;


    // Status constants
    const STATUS_COMPLETED = "completed";
    const STATUS_FAILED = "failed";

    /**
     * @param string $objectClass
     * @param string $objectPk
     * @param string $stepKey
     * @param string $status
     * @param string $logOutput
     * @param string $triggerValue
     * @param \DateTime $completedTime
     */
    public function __construct($objectClass, $objectPk, $stepKey, $status, $logOutput, $triggerValue = null, $completedTime = null) {
        $this->objectClass = $objectClass;
        $this->objectPk = $objectPk;
        $this->stepKey = $stepKey;
        $this->completedTime = $completedTime ?? new \DateTime();
        $this->status = $status;
        $this->logOutput = $logOutput;
        $this->triggerValue = $triggerValue;
    }


    /**
     * @return string
     */
    public function getObjectClass() {
        return $this->objectClass;
    }

    /**
     * @param string $objectClass
     */
    public function setObjectClass($objectClass) {
        $this->objectClass = $objectClass;
    }

    /**
     * @return string
     */
    public function getObjectPk() {
        return $this->objectPk;
    }

    /**
     * @param string $objectPk
     */
    public function setObjectPk($objectPk) {
        $this->objectPk = $objectPk;
    }

    /**
     * @return string
     */
    public function getStepKey() {
        return $this->stepKey;
    }

    /**
     * @param string $stepKey
     */
    public function setStepKey($stepKey) {
        $this->stepKey = $stepKey;
    }

    /**
     * @return \DateTime
     */
    public function getCompletedTime() {
        return $this->completedTime;
    }

    /**
     * @param \DateTime $completedTime
     */
    public function setCompletedTime($completedTime) {
        $this->completedTime = $completedTime;
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
    public function getLogOutput() {
        return $this->logOutput;
    }

    /**
     * @param string $logOutput
     */
    public function setLogOutput($logOutput) {
        $this->logOutput = $logOutput;
    }

    /**
     * @return string|null
     */
    public function getTriggerValue() {
        return $this->triggerValue;
    }

    /**
     * @param string|null $triggerValue
     */
    public function setTriggerValue($triggerValue) {
        $this->triggerValue = $triggerValue;
    }


}