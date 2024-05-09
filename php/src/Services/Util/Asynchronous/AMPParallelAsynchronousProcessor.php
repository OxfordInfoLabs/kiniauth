<?php

namespace Kiniauth\Services\Util\Asynchronous;

use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Parallel\Worker\ContextWorkerPool;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Amp\TimeoutCancellation;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\Session;
use Kinikit\Core\Asynchronous\Asynchronous;
use Kinikit\Core\Asynchronous\Processor\AsynchronousProcessor;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use function Amp\Future\await;
use function Amp\Future\awaitAll;
use function Amp\Parallel\Worker\submit;
use function Amp\Parallel\Worker\workerPool;

class AMPParallelAsynchronousProcessor implements AsynchronousProcessor {

    public function __construct(
        private ClassInspectorProvider $classInspectorProvider
    ) {
    }

    public function executeAndWait($asynchronousInstances, $timeout = 120) {
        $configEnvironmentVar = getenv("KINIKIT_CONFIG_FILE");

        $session = Container::instance()->get(Session::class);
        $securableId = $session->__getLoggedInSecurable() ? $session->__getLoggedInSecurable()->getId() : null;
        $securableType = $session->__getLoggedInSecurable() ? ($session->__getLoggedInSecurable() instanceof User ? "USER" : "API_KEY") : null;
        $accountId = $session->__getLoggedInAccount() ? $session->__getLoggedInAccount()->getAccountId() : 0;

        $workerPool = new ContextWorkerPool();

        // Turn an async instance to a future using an AMPParallelTask wrapper.
        $toFuture = fn(Asynchronous $instance) => $workerPool->submit(
            new AMPParallelTask($instance, $securableId, $securableType, $configEnvironmentVar, $accountId, $timeout)
        )->getFuture();

        // Await execution of all queued tasks.

        $responses = await(array_map($toFuture, $asynchronousInstances));

        // Grab response instances and resync original instances for reference integrity.
        foreach ($responses as $index => $response) {
            $classInspector = $this->classInspectorProvider->getClassInspector(get_class($response));
            $properties = $classInspector->getPropertyData($response, null, false);
            $classInspector->setPropertyData($asynchronousInstances[$index], $properties, null, false);
        }

        $nFailed = count(array_filter($asynchronousInstances, fn($instance) => $instance->getStatus() == Asynchronous::STATUS_FAILED));
        $nInstances = count($asynchronousInstances);
        Logger::log("Parallel Processor Errors: " . $nFailed . " || Parallel Processor AsyncInstances " . $nInstances);

        $workerPool->kill();
        $workerPool->shutdown();
        unset($workerPool);

        return $asynchronousInstances;

    }
}