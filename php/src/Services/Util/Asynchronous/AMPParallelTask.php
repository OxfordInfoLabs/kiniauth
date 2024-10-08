<?php

namespace Kiniauth\Services\Util\Asynchronous;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Amp\TimeoutException;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\Services\Util\Asynchronous\AsynchronousProcessor;
use Kinikit\Core\Asynchronous\Asynchronous;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Bootstrapper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\awaitFirst;


class AMPParallelTask implements Task {

    public function __construct(
        private Asynchronous $asynchronous,
        private $securableId,
        private $securableType,
        private string $configEnvironmentVar,
        private ?int $accountId,
        private int $timeout = 120
    ) {
    }

    public static bool $inParallel = false;

    public function run(Channel $channel, Cancellation $cancellation): mixed {
        self::$inParallel = true;
        // This looks a bit more complicated than necessary.
        // The reason for this is that I tried to use the builtin cancellation methods for timeout,
        // but this led to, upon a long function being interrupted in a try catch block:
        //
        // try { longTask(); ***CancellationException thrown here*** } catch (Exception $e) { /* behaviour */ }
        //
        // This means that the task was not successfully cancelled.
        // The awaitFirst method puts the parent thread in charge of the cancellation.
        putenv("KINIKIT_CONFIG_FILE=$this->configEnvironmentVar");
        try {
            $result = awaitFirst([
                async(fn() => $this->runAsynchronous()),
                async(function () {
                    delay($this->timeout, false);
                    throw new TimeoutException();
                })
            ]);
            return $result;
        } catch (\Exception $exception){
            if ($exception instanceof TimeoutException) {
                // Timed out
                Logger::log("TIMED OUT WITH ASYNC " . get_class($this->asynchronous));
            }

            $this->asynchronous->setStatus(Asynchronous::STATUS_FAILED);
            $objectBinder = Container::instance()->get(ObjectBinder::class);
            $exceptionArray = $objectBinder->bindToArray($exception);
            $this->asynchronous->setExceptionData($exceptionArray);
            return $this->asynchronous;
        }
    }

    private function runAsynchronous(){
        Container::instance()->get(Bootstrapper::class);

        $securityService = Container::instance()->get(SecurityService::class);

        if ($this->securableId) {
            $securityService->becomeSecurable($this->securableType, $this->securableId);
        } else if ($this->accountId) {
            $securityService->becomeAccount($this->accountId);
        } else if ($this->accountId === 0) {
            $securityService->becomeSuperUser();
        }

        // Attempt to run asynchronous and set accordingly
        try {
            $result = $this->asynchronous->run();
            $this->asynchronous->setStatus(Asynchronous::STATUS_COMPLETED);
            $this->asynchronous->setReturnValue($result);
        } catch (\Exception $e) {
            Logger::log("TASK FAILED WITH EXCEPTION: " . $e->getMessage());
            Logger::log(get_class($e));
            $this->asynchronous->setStatus(Asynchronous::STATUS_FAILED);
            $objectBinder = Container::instance()->get(ObjectBinder::class);
            $exceptionArray = $objectBinder->bindToArray($e);
            if (is_array($exceptionArray)) {
                unset($exceptionArray["file"]);
                unset($exceptionArray["line"]);
                unset($exceptionArray["previous"]);
                unset($exceptionArray["trace"]);
                unset($exceptionArray["traceAsString"]);
            }
            $this->asynchronous->setExceptionData($exceptionArray);

        }
        return $this->asynchronous;
    }
}