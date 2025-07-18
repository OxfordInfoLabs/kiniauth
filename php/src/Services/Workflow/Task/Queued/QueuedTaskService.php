<?php


namespace Kiniauth\Services\Workflow\Task\Queued;

use Kiniauth\Exception\QueuedTask\NoQueuedTaskImplementationException;
use Kiniauth\Services\Workflow\Task\Queued\Processor\QueuedTaskProcessor;
use Kiniauth\ValueObjects\QueuedTask\QueueItem;
use Kinikit\Core\Configuration\ConfigFile;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;

/**
 * Queued task service.
 *
 * Class QueuedTaskService
 */
class QueuedTaskService {

    /**
     * @var string[string]
     */
    private $taskClasses;

    /**
     * @var QueuedTaskProcessor
     */
    private $queuedTaskProcessor;


    /**
     * @var FileResolver
     */
    private $fileResolver;


    /**
     * QueuedTaskService constructor.
     *
     * @param QueuedTaskProcessor $queuedTaskProcessor
     * @param FileResolver $fileResolver
     */
    public function __construct($queuedTaskProcessor, $fileResolver) {
        $this->queuedTaskProcessor = $queuedTaskProcessor;
        $this->fileResolver = $fileResolver;
    }


    /**
     * Queue a task for asynchronous processing using the default queue manager.  Returns the
     * implementation specific identifier for the task.
     *
     * @param $queueName
     * @param $taskIdentifier
     * @param $description
     * @param string[string] $configuration
     * @param \DateTime $runDateTime
     * @param integer $runOffsetSeconds
     *
     * @return string
     */
    public function queueTask($queueName, $taskIdentifier, $description, $configuration = [], $runDateTime = null, $runOffsetSeconds = null) {

        $startTime = $runDateTime;
        if ($runOffsetSeconds !== null) {
            $startTime = new \DateTime();
            $startTime->add(new \DateInterval("PT" . $runOffsetSeconds . "S"));
        }

        return $this->queuedTaskProcessor->queueTask($queueName, $taskIdentifier, $description, $configuration, $startTime);
    }


    /**
     * List queued tasks
     */
    public function listQueuedTasks($queueName) {
        return $this->queuedTaskProcessor->listQueuedTasks($queueName);
    }

    /**
     * Process a queued task using a passed identifier and optional configuration.
     *
     * @param $taskIdentifier
     * @param array $configuration
     */
    public function processQueuedTask($queueName, $taskIdentifier, $taskInstanceIdentifier, $configuration = []) {
        $this->loadTaskClasses();
        if (isset($this->taskClasses[$taskIdentifier])) {

            $this->queuedTaskProcessor->registerTaskStatusChange($queueName, $taskInstanceIdentifier, QueueItem::STATUS_RUNNING);

            Container::instance()->get($this->taskClasses[$taskIdentifier])->run($configuration);

            $this->queuedTaskProcessor->registerTaskStatusChange($queueName, $taskInstanceIdentifier, QueueItem::STATUS_COMPLETED);
            $this->queuedTaskProcessor->deQueueTask($queueName, $taskInstanceIdentifier);


        } else {
            throw new NoQueuedTaskImplementationException($taskIdentifier);
        }
    }


    /**
     * Process the next queued task - this is where scheduling is manual (e.g. default task scheduler).
     *
     * @param $taskIdentifier
     * @param array $configuration
     */
    public function processNextQueuedTask($queueName) {
        $tasks = $this->queuedTaskProcessor->listQueuedTasks($queueName);
        if (sizeof($tasks) > 0) {
            $this->processQueuedTask($tasks[0]->getQueueName(), $tasks[0]->getTaskIdentifier(), $tasks[0]->getIdentifier(), $tasks[0]->getConfiguration());
        }
    }


    /**
     * Get installed task classes
     */
    public function getInstalledTaskClasses() {
        $this->loadTaskClasses();
        return $this->taskClasses;
    }


    // Load product classes if required
    private function loadTaskClasses() {
        if (!$this->taskClasses) {
            $this->taskClasses = [];
            foreach ($this->fileResolver->getSearchPaths() as $searchPath) {
                $config = new ConfigFile($searchPath . "/Config/queued-tasks.txt");
                $this->taskClasses = array_merge($this->taskClasses, $config->getAllParameters());
            }
        }
    }

}
