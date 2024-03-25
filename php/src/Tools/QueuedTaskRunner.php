<?php

namespace Kiniauth\Tools;

use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kinikit\Core\Bootstrapper;
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

        $queueName = $event->getComposer()->getPackage()->getConfig()["queue-name"] ?? "default-queue";
        $sourceDirectory = $event->getComposer()->getPackage()->getConfig()["source-directory"] ?? "src";

        chdir($sourceDirectory);

        // Ensure autoloader run from vendor.
        include_once "../vendor/autoload.php";

        // Ensure basic initialisation has occurred.
        Container::instance()->get(Bootstrapper::class);

        Container::instance()->get(QueuedTaskRunner::class)->run($queueName);
    }

}
