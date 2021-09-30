<?php


namespace Kiniauth\Tools;


use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kinikit\Core\Bootstrapper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Init;

class ScheduledTaskRunner {

    /**
     * @var ScheduledTaskService
     */
    private $scheduledTaskService;


    /**
     * ScheduledTaskRunner constructor.
     *
     * @param ScheduledTaskService $scheduledTaskService
     */
    public function __construct($scheduledTaskService) {
        $this->scheduledTaskService = $scheduledTaskService;
    }


    /**
     * Run all due tasks
     */
    public function run() {
        $this->scheduledTaskService->processDueTasks();
    }

    /**
     * Main composer execution function
     */
    public static function runFromComposer($event) {

        $sourceDirectory = $event->getComposer()->getPackage()->getConfig()["source-directory"] ?? "src";

        chdir($sourceDirectory);

        // Ensure autoloader run from vendor.
        include_once "../vendor/autoload.php";

        // Ensure basic initialisation has occurred.
        Container::instance()->get(Init::class);
        Container::instance()->get(Bootstrapper::class);

        Container::instance()->get(ScheduledTaskRunner::class)->run();
    }


}