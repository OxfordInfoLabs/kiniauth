<?php

namespace Kiniauth\Objects\Workflow;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_object_workflow_step
 * @generate
 */
class ObjectWorkflowStep extends ActiveRecord {

    /**
     * @var string
     * @primaryKey
     */
    private $objectClass;

    /**
     * A key identifying this step
     *
     * @var string
     * @primaryKey
     */
    private $stepKey;


    /**
     * A description for this step
     *
     * @var string
     */
    private $stepDescription;


    /**
     * @var string
     * @values manual,date_offset_days,property_change
     */
    private $stepTrigger;

    /**
     * @var string
     */
    private $stepTriggerData;


    /**
     * @var string
     */
    private $taskIdentifier;


    /**
     * @var mixed
     * @sqlType LONGTEXT
     * @json
     */
    private $taskConfiguration;

    /**
     * Workflow trigger
     */
    const TRIGGER_MANUAL = "manual";
    const TRIGGER_DATE_OFFSET_DAYS = "date_offset_days";
    const TRIGGER_PROPERTY_CHANGE = "property_change";

    /**
     * @param string $objectClass
     * @param string $stepKey
     * @param string $stepDescription
     * @param string $stepTrigger
     * @param string $taskIdentifier
     * @param mixed $taskConfiguration
     * @param string $stepTriggerData
     * @param null $stepCriteria
     */
    public function __construct($objectClass, $stepKey, $stepDescription, $stepTrigger, $taskIdentifier, $taskConfiguration = [], $stepTriggerData = null) {
        $this->objectClass = $objectClass;
        $this->stepKey = $stepKey;
        $this->stepDescription = $stepDescription;
        $this->stepTrigger = $stepTrigger;
        $this->stepTriggerData = $stepTriggerData;
        $this->taskIdentifier = $taskIdentifier;
        $this->taskConfiguration = $taskConfiguration;
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
     * @return string
     */
    public function getStepDescription() {
        return $this->stepDescription;
    }

    /**
     * @param string $stepDescription
     */
    public function setStepDescription($stepDescription) {
        $this->stepDescription = $stepDescription;
    }

    /**
     * @return string
     */
    public function getStepTrigger() {
        return $this->stepTrigger;
    }

    /**
     * @param string $stepTrigger
     */
    public function setStepTrigger($stepTrigger) {
        $this->stepTrigger = $stepTrigger;
    }

    /**
     * @return string
     */
    public function getStepTriggerData() {
        return $this->stepTriggerData;
    }

    /**
     * @param string $stepTriggerData
     */
    public function setStepTriggerData($stepTriggerData) {
        $this->stepTriggerData = $stepTriggerData;
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
    public function getTaskConfiguration() {
        return $this->taskConfiguration;
    }

    /**
     * @param string $taskConfiguration
     */
    public function setTaskConfiguration($taskConfiguration) {
        $this->taskConfiguration = $taskConfiguration;
    }


}