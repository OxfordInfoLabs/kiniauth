<?php

namespace Kiniauth\Services\Util\Asynchronous;

use Amp\TimeoutCancellation;
use Kinikit\Core\Asynchronous\Asynchronous;
use Kinikit\Core\Asynchronous\Processor\AsynchronousProcessor;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use function Amp\async;
use function Amp\Future\await;

class AMPAsyncAsynchronousProcessor implements AsynchronousProcessor {

    public function __construct(
        private ClassInspectorProvider $classInspectorProvider
    ) {
    }

    /**
     * @template T of Asynchronous
     * @param T[] $asynchronousInstances
     * @param $timeout
     * @return T[]
     */
    public function executeAndWait($asynchronousInstances, $timeout = 120) {

        $toFuture = fn(Asynchronous $instance) => async(function () use ($instance){
            try {
                $result = $instance->run();
                $instance->setStatus(Asynchronous::STATUS_COMPLETED);
                $instance->setReturnValue($result);
            } catch (\Exception $e) {
                $instance->setStatus(Asynchronous::STATUS_FAILED);
                $objectBinder = Container::instance()->get(ObjectBinder::class);
                $exceptionArray = $objectBinder->bindToArray($e);
                if (is_array($exceptionArray)) {
                    unset($exceptionArray["file"]);
                    unset($exceptionArray["line"]);
                    unset($exceptionArray["previous"]);
                    unset($exceptionArray["trace"]);
                    unset($exceptionArray["traceAsString"]);
                }
                $instance->setExceptionData($exceptionArray);

            }
            return $instance;
        });

        $responses = await(
            array_map($toFuture, $asynchronousInstances),
            new TimeoutCancellation($timeout));
        ksort($responses);
        return $responses;
    }
}