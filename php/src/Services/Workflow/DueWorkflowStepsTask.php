<?php

namespace Kiniauth\Services\Workflow;

use Kiniauth\Services\Workflow\Task\Task;

class DueWorkflowStepsTask implements Task {

    /**
     * @var ObjectWorkflowService
     */
    private $objectWorkflowService;

    /**
     * Construct task
     *
     * @param ObjectWorkflowService $objectWorkflowService
     */
    public function __construct(ObjectWorkflowService $objectWorkflowService) {
        $this->objectWorkflowService = $objectWorkflowService;
    }


    /**
     *
     *
     * @param $configuration
     * @return void
     */
    public function run($configuration) {
        if (isset($configuration["classes"]) && is_array($configuration["classes"])) {
            foreach ($configuration["classes"] as $class) {
                $this->objectWorkflowService->processDueWorkflowStepsForObjectClass($class);
            }
        }
    }
}