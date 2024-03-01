<?php

namespace Kiniauth\Exception\Workflow;

use Kinikit\Core\Exception\ItemNotFoundException;

class ObjectWorkflowStepNotFoundException extends ItemNotFoundException {

    public function __construct($className, $stepKey) {
        parent::__construct("The step key '$stepKey' has not been defined for workflow on the class '$className'");
    }

}