<?php

namespace Kiniauth\Services\Util\Asynchronous;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\Asynchronous\Asynchronous;
use Kiniauth\Test\Services\Util\Asynchronous\AsynchronousProcessor;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Bootstrapper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Template\ValueFunction\ValueFunctionEvaluator;


class AMPParallelTask implements Task {

    public function __construct(
        private Asynchronous $asynchronous,
        private $securableId,
        private $securableType,
        private ?int $accountId
    ) {
    }

    public function run(Channel $channel, Cancellation $cancellation): mixed {

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