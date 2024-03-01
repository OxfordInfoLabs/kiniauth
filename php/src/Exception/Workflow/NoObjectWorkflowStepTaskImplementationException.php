<?php

namespace Kiniauth\Exception\Workflow;

use Kinikit\Core\Exception\ItemNotFoundException;

class NoObjectWorkflowStepTaskImplementationException extends ItemNotFoundException {

    public function __construct($taskIdentifier) {
        parent::__construct("No object workflow step task implementation exists for task identifier $taskIdentifier");
    }

}