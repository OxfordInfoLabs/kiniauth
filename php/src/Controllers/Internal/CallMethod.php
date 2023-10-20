<?php

namespace Kiniauth\Controllers\Internal;

use Kiniauth\Services\Security\SecurityService;
use Kiniauth\ValueObjects\Util\Asynchronous\HttpLoopbackRequest;
use Kiniauth\ValueObjects\Util\Asynchronous\HttpLoopbackResponse;
use Kinikit\Core\Asynchronous\AsynchronousClassMethod;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Logging\Logger;


class CallMethod {

    /**
     * @var ObjectBinder
     */
    private $objectBinder;


    /**
     * @var SecurityService
     */
    private $securityService;


    /**
     * @param ObjectBinder $objectBinder
     * @param SecurityService $securityService
     */
    public function __construct($objectBinder, $securityService) {
        $this->objectBinder = $objectBinder;
        $this->securityService = $securityService;
    }


    /**
     * Call a method on a class using a loopback request and return a loopback response
     *
     * @http POST /
     *
     * @param HttpLoopbackRequest $httpLoopbackRequest
     * @return HttpLoopbackResponse
     */
    public function callMethod($httpLoopbackRequest) {

        if ($httpLoopbackRequest->getSecurableId()) {
            $this->securityService->becomeSecurable($httpLoopbackRequest->getSecurableType(), $httpLoopbackRequest->getSecurableId());
        } else if ($httpLoopbackRequest->getAccountId()) {
            $this->securityService->becomeAccount($httpLoopbackRequest->getAccountId());
        } else if ($httpLoopbackRequest->getAccountId() === 0) {
            $this->securityService->becomeSuperUser();
        }

        $boundParameters = [];
        $parameterTypes = $httpLoopbackRequest->getParameterValueTypes();
        foreach ($httpLoopbackRequest->getParameterValues() as $index => $value) {
            $boundParameters[$index] = $this->objectBinder->bindFromArray($value, $parameterTypes[$index]);
        }

        // Create an asynchronous
        $asynchronous = new AsynchronousClassMethod($httpLoopbackRequest->getClassName(), $httpLoopbackRequest->getMethodName(), $boundParameters);

        try {
            // Execute method and get result
            $result = $asynchronous->run();

            // Derive return type either directly or from request object
            $returnType = is_object($result) ? get_class($result) : $httpLoopbackRequest->getReturnType();

            // Return the response
            return new HttpLoopbackResponse(HttpLoopbackResponse::STATUS_SUCCESS, $result, $returnType);
        } catch (\Exception $e) {

            Logger::log($e->getMessage());
            return new HttpLoopbackResponse(HttpLoopbackResponse::STATUS_EXCEPTION, $e, get_class($e));
        }

    }

}