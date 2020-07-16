<?php

namespace Kiniauth\Exception\QueuedTask;

use Kinikit\Core\Exception\ItemNotFoundException;

/**
 * No queued task implementation
 *
 * Class NoQueuedTaskImplementationException
 */
class NoQueuedTaskImplementationException extends ItemNotFoundException {

    public function __construct($taskIdentifier) {
        parent::__construct("No queued task implementation exists for task identifier $taskIdentifier");
    }

}
