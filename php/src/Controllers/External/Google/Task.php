<?php

namespace Kiniauth\Controllers\External\Google;

use Kiniauth\Exception\QueuedTask\NoQueuedTaskImplementationException;
use Kinikit\Core\Configuration\ConfigFile;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Response\JSONResponse;
use Kinikit\MVC\Response\Response;

class Task {

    /**
     * @var FileResolver
     */
    private $fileResolver;

    /**
     * @var string[string]
     */
    private $taskClasses;

    /**
     * Google Queued Task Controller constructor.
     *
     * @param FileResolver $fileResolver
     */
    public function __construct($fileResolver) {
        $this->fileResolver = $fileResolver;
    }

    /**
     * @http POST /
     *
     * @param array $payload
     * @return void
     * @throws NoQueuedTaskImplementationException
     */
    public function processTask($payload): void {
        Logger::log("Task Endpoint Reached");
        $this->loadTaskClasses();
        Logger::log($this->taskClasses);

        $taskIdentifier = $payload["taskIdentifier"];
        $taskConfiguration = $payload["configuration"];

        if (isset($this->taskClasses[$taskIdentifier])) {
            Container::instance()->get($this->taskClasses[$taskIdentifier])->run($taskConfiguration);
        }
        else {
            throw new NoQueuedTaskImplementationException($taskIdentifier);
        }
    }

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