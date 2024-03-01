<?php

namespace Kiniauth\Services\Workflow;

use Kiniauth\Exception\Workflow\NoObjectWorkflowStepTaskImplementationException;
use Kiniauth\Exception\Workflow\ObjectWorkflowStepNotFoundException;
use Kiniauth\Objects\Workflow\ObjectWorkflowCompletedStep;
use Kiniauth\Objects\Workflow\ObjectWorkflowStep;
use Kiniauth\Services\Workflow\Task\Task;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use Kinikit\Core\Util\StringUtils;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\ORM\Mapping\ORMMapping;
use Kinikit\Persistence\ORM\ORM;

/**
 * Process object workflows according to the steps defined for a given object class.
 */
class ObjectWorkflowService {

    /**
     * @var ORM
     */
    private $orm;


    /**
     * @var ClassInspectorProvider
     */
    private $classInspectorProvider;

    /**
     * @param ORM $orm
     * @param ClassInspectorProvider $classInspectorProvider
     */
    public function __construct($orm, $classInspectorProvider) {
        $this->orm = $orm;
        $this->classInspectorProvider = $classInspectorProvider;
    }


    /**
     * Process a workflow step for a given object class, pk and step key
     *
     * @param $objectClass
     * @param $objectPk
     * @param $stepKey
     * @return void
     */
    public function processWorkflowStep($objectClass, $objectPk, $stepKey, $triggerValue = "N/A") {


        // Check that we haven't already recorded a completed step for this object and key
        try {
            $completedStep = ObjectWorkflowCompletedStep::fetch([$objectClass, $objectPk, $stepKey, $triggerValue]);
            if ($completedStep->getStatus() == ObjectWorkflowCompletedStep::STATUS_COMPLETED)
                return;
        } catch (ObjectNotFoundException $e) {
        }


        try {

            /**
             * @var ObjectWorkflowStep $workflowStep
             */
            $workflowStep = ObjectWorkflowStep::fetch([$objectClass, $stepKey]);

            /**
             * @var Task $task
             */
            $task = Container::instance()->getInterfaceImplementation(Task::class, $workflowStep->getTaskIdentifier());

            try {

                // Run the task with the task configuration
                $result = $task->run(["workflowStep" => $workflowStep, "objectPk" => $objectPk]);
                $status = ObjectWorkflowCompletedStep::STATUS_COMPLETED;
            } catch (\Exception $e) {
                $result = $e->getMessage();
                $status = ObjectWorkflowCompletedStep::STATUS_FAILED;
            }

            // Create and save the completed step
            $completedStep = new ObjectWorkflowCompletedStep($objectClass, $objectPk, $stepKey, $status, $result, $triggerValue);
            $completedStep->save();


        } catch (ObjectNotFoundException $e) {
            throw new ObjectWorkflowStepNotFoundException($objectClass, $stepKey);
        } catch (MissingInterfaceImplementationException $e) {
            throw new NoObjectWorkflowStepTaskImplementationException($workflowStep->getTaskIdentifier());
        }


    }


    /**
     * Process property change workflow steps for a given object.  This is usually called from
     * the generic object interceptor
     *
     * @param string $objectClass
     * @param mixed $objectPk
     * @param mixed $previousObject
     * @param mixed $newObject
     *
     * @return void
     */
    public function processPropertyChangeWorkflowSteps($objectClass, $objectPk, $previousObject, $newObject) {


        // If not new object, return
        if (!$newObject)
            return;

        // Grab class inspector
        $classInspector = $this->classInspectorProvider->getClassInspector($objectClass);

        // Grab all steps
        $workflowSteps = ObjectWorkflowStep::filter("WHERE objectClass = ? AND stepTrigger = ?", $objectClass, ObjectWorkflowStep::TRIGGER_PROPERTY_CHANGE);

        // Loop through each workflow step and check for property changes
        /**
         * @var ObjectWorkflowStep $workflowStep
         */
        foreach ($workflowSteps as $workflowStep) {

            // Get old and new values and compare
            $oldValue = $previousObject ? $classInspector->getPropertyData($previousObject, $workflowStep->getStepTriggerData()) : null;
            $newValue = $classInspector->getPropertyData($newObject, $workflowStep->getStepTriggerData());

            if ($oldValue != $newValue) {
                $this->processWorkflowStep($objectClass, $objectPk, $workflowStep->getStepKey(), md5(json_encode($newValue) . date("Y-m-d H:i:s")));
            }

        }

    }


    /**
     * Process all workflow steps due for a given object class.  These refer
     * to steps defined with a trigger type of date_offset_days.
     *
     * @param $objectClass
     * @return void
     */
    public function processDueWorkflowStepsForObjectClass($objectClass) {

        $workflowSteps = ObjectWorkflowStep::filter("WHERE objectClass = ? AND stepTrigger = ?", $objectClass, ObjectWorkflowStep::TRIGGER_DATE_OFFSET_DAYS);

        // Group the steps by field and date
        $groupedSteps = [];
        foreach ($workflowSteps as $workflowStep) {
            $triggerData = explode(":", $workflowStep->getStepTriggerData());
            if (!isset($groupedSteps[$triggerData[0]])) {
                $groupedSteps[$triggerData[0]] = [];
            }
            $groupedSteps[$triggerData[0]][$workflowStep->getStepKey()] = $triggerData[1];

            arsort($groupedSteps[$triggerData[0]]);
        }

        // Loop through each field identified
        foreach ($groupedSteps as $field => $steps) {

            // Grab pk column names
            $ormMapping = ORMMapping::get($objectClass);
            $table = $ormMapping->getReadTableMapping()->getTableName();
            $pkNames = $ormMapping->getReadTableMapping()->getPrimaryKeyColumnNames();

            $pkName = StringUtils::convertToSnakeCase($pkNames[0]);
            $field = StringUtils::convertToSnakeCase($field);

            $stepIndex = "CASE o.step_key ";
            foreach ($steps as $key => $index) {
                $stepIndex .= " WHEN '$key' THEN $index";
            }
            $stepIndex .= " END";

            $query = "SELECT t.$pkName pk, t.$field trigger_field, 
                LAST_VALUE(o.step_key)
                OVER (PARTITION BY t.$pkName, t.$field ORDER BY $stepIndex) latest_step_key
                FROM $table t
                LEFT JOIN ka_object_workflow_completed_step o
                ON o.object_pk = t.$pkName
                AND o.object_class = ? 
                AND o.trigger_value = t.$field
                LEFT JOIN ka_object_workflow_step s
                        ON s.object_class = o.object_class
                        AND s.step_trigger = ?
                ORDER BY t.$pkName";


            $results = $ormMapping->getReadTableMapping()->getDatabaseConnection()->query($query, $objectClass, ObjectWorkflowStep::TRIGGER_DATE_OFFSET_DAYS);


            $now = new \DateTime();

            // Read results and process accordingly
            while ($row = $results->nextRow()) {

                if ($row["trigger_field"]) {
                    $triggerDate = new \DateTime($row["trigger_field"]);
                    $latestStepKey = $row["latest_step_key"];

                    // Check all steps in descending order
                    foreach ($steps as $stepKey => $offset) {

                        // If we've already processed this step, break.
                        if ($stepKey == $latestStepKey)
                            break;

                        // If this is a candidate for triggering, do this now.
                        if ($now->diff($triggerDate)->days >= $offset) {
                            $this->processWorkflowStep($objectClass, $row["pk"], $stepKey, $row["trigger_field"]);
                            break;
                        }


                    }
                }
            }

        }

    }



}