<?php

namespace Kiniauth\Tools;

use Kiniauth\Services\Workflow\QueuedTask\QueuedTaskService;
use Kinikit\Core\DependencyInjection\Container;

class QueuedTaskRunner {


    /**
     * @var QueuedTaskService
     */
    private $queuedTaskService;


    /**
     * DefaultQueuedTaskRunner constructor.
     *
     * @param QueuedTaskService $queuedTaskService
     */
    public function __construct($queuedTaskService) {
        $this->queuedTaskService = $queuedTaskService;
    }


    /**
     * Pop the next task off the list and run it.
     */
    public function run($queueName) {
        $this->queuedTaskService->processNextQueuedTask($queueName);
    }

    /**
     * Main composer execution function
     */
    public static function runFromComposer($event) {
        Container::instance()->get(QueuedTaskRunner::class)->run();
    }

}
