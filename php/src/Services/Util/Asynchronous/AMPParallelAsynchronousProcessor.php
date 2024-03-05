<?php

namespace Kiniauth\Services\Util\Asynchronous;

use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\Session;
use Kinikit\Core\Asynchronous\Asynchronous;
use Kinikit\Core\Asynchronous\Processor\AsynchronousProcessor;
use Kinikit\Core\Asynchronous\Processor\ParallelProcessor;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use function Amp\Future\await;
use function Amp\Parallel\Worker\submit;

class AMPParallelAsynchronousProcessor implements AsynchronousProcessor {

    public function __construct(
        private ClassInspectorProvider $classInspectorProvider
    ) {
    }

    public function executeAndWait($asynchronousInstances) {

        $session = Container::instance()->get(Session::class);
        $securableId = $session->__getLoggedInSecurable() ? $session->__getLoggedInSecurable()->getId() : null;
        $securableType = $session->__getLoggedInSecurable() ? ($session->__getLoggedInSecurable() instanceof User ? "USER" : "API_KEY") : null;
        $accountId = $session->__getLoggedInAccount() ? $session->__getLoggedInAccount()->getAccountId() : 0;

        // Turn an async instance to a future using an AMPParallelTask wrapper.
        $toFuture = fn(Asynchronous $instance) => submit(new AMPParallelTask($instance, $securableId, $securableType, $accountId))->getFuture();

        // Await execution of all queued tasks.
        $responses = await(
            array_map($toFuture, $asynchronousInstances)
        );

        // Grab response instances and resync original instances for reference integrity.
        foreach ($responses as $index => $response) {
            $classInspector = $this->classInspectorProvider->getClassInspector(get_class($response));
            $properties = $classInspector->getPropertyData($response, null, false);
            $classInspector->setPropertyData($asynchronousInstances[$index], $properties, null, false);
        }

        return $asynchronousInstances;

    }
}