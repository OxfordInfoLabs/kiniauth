<?php

namespace Kiniauth\Services\Workflow\QueuedTask;

/**
 * Queued task
 *
 * Interface QueuedTask
 */
interface QueuedTask {

    /**
     * Run method for a queued task.  Returns true or false
     * according to whether this task was successful or failed.
     *
     * @param string[string] $configuration
     * @return boolean
     */
    public function run($configuration);


}
