<?php

namespace Kiniauth\Services\Workflow\Task;

use Kiniauth\Objects\Workflow\ObjectWorkflowStep;

abstract class ObjectWorkflowStepTask implements Task {


    /**
     * Implement run to pass through to a run step with rational parameters
     *
     * @param $configuration
     * @return void
     */
    public function run($configuration) {
        $this->runWorkflowStep($configuration["workflowStep"] ?? null, $configuration["objectPk"] ?? null);
    }


    /**
     * Run a workflow step with rational arguments from run
     *
     * @param ObjectWorkflowStep $workflowStep
     * @param mixed $objectPk
     */
    public abstract function runWorkflowStep($workflowStep, $objectPk);


}