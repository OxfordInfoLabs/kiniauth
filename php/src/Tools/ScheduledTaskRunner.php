<?php


namespace Kiniauth\Tools;


use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kinikit\Core\Bootstrapper;
use Kinikit\Core\DependencyInjection\Container;

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
    public function run($taskGroup = null) {
        $this->scheduledTaskService->processDueTasks($taskGroup);
    }

    /**
     * Main composer execution function
     */
    public static function runFromComposer($event) {

        // Get task group if supplied
        $taskGroup = $event->getArguments()[0] ?? null;

        // Wait seconds if defined
        $waitSeconds = $event->getArguments()[1] ?? 0;
        sleep($waitSeconds);

        $sourceDirectory = $event->getComposer()->getPackage()->getConfig()["source-directory"] ?? "src";

        chdir($sourceDirectory);

        // Ensure autoloader run from vendor.
        include_once "../vendor/autoload.php";


        // Ensure basic initialisation has occurred.
        Container::instance()->get(Bootstrapper::class);

        /**
         * @var ActiveRecordInterceptor $activeRecordInterceptor
         */
        $activeRecordInterceptor = Container::instance()->get(ActiveRecordInterceptor::class);

        // Execute the scheduled tasks with interceptor disabled
        $activeRecordInterceptor->executeInsecure(function () use ($taskGroup) {
            Container::instance()->get(ScheduledTaskRunner::class)->run($taskGroup);
        });

    }


}