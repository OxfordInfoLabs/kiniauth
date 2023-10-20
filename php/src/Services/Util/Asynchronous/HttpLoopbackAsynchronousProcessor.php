<?php

namespace Kiniauth\Services\Util\Asynchronous;

use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\Session;
use Kiniauth\ValueObjects\Util\Asynchronous\HttpLoopbackRequest;
use Kiniauth\ValueObjects\Util\Asynchronous\HttpLoopbackResponse;
use Kinikit\Core\Asynchronous\Asynchronous;
use Kinikit\Core\Asynchronous\AsynchronousClassMethod;
use Kinikit\Core\Asynchronous\Processor\AsynchronousProcessor;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Exception\WrongParametersException;
use Kinikit\Core\HTTP\Dispatcher\HttpMultiRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use Kinikit\Core\Security\Hash\SHA512HashProvider;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;

/**
 * Asynchronous processor which implements multi threading by using
 * an Http Multi Request Dispatcher to make multiple HTTP requests back to the main application
 * to execute a method on a class.
 */
class HttpLoopbackAsynchronousProcessor implements AsynchronousProcessor {

    /**
     * @var HttpMultiRequestDispatcher
     */
    private $multiRequestDispatcher;

    /**
     * @var SHA512HashProvider
     */
    private $hashProvider;

    /**
     * @var ClassInspectorProvider
     */
    private $classInspectorProvider;


    /**
     * @var ObjectBinder
     */
    private $objectBinder;


    /**
     * @var ObjectToJSONConverter
     */
    private $objectToJSONConverter;


    /**
     * @var Session
     */
    private $session;


    /**
     * @param HttpMultiRequestDispatcher $multiRequestDispatcher
     * @param SHA512HashProvider $hashProvider
     * @param ClassInspectorProvider $classInspectorProvider
     * @param ObjectBinder $objectBinder
     * @param ObjectToJSONConverter $objectToJSONConverter
     * @param Session $session
     */
    public function __construct($multiRequestDispatcher, $hashProvider, $classInspectorProvider, $objectBinder, $objectToJSONConverter, $session) {
        $this->multiRequestDispatcher = $multiRequestDispatcher;
        $this->hashProvider = $hashProvider;
        $this->classInspectorProvider = $classInspectorProvider;
        $this->objectBinder = $objectBinder;
        $this->objectToJSONConverter = $objectToJSONConverter;
        $this->session = $session;
    }

    /**
     * Execute processor and wait for responses
     *
     * @param Asynchronous[] $asynchronousInstances
     * @return Asynchronous[]
     */
    public function executeAndWait($asynchronousInstances) {


        /**
         * @var Session $session
         */
        $securableId = $this->session->__getLoggedInSecurable() ? $this->session->__getLoggedInSecurable()->getId() : null;
        $securableType = $this->session->__getLoggedInSecurable() ? ($this->session->__getLoggedInSecurable() instanceof User ? "USER" : "API_KEY") : null;
        $accountId = $this->session->__getLoggedInAccount() ? $this->session->__getLoggedInAccount()->getAccountId() : 0;

        // Check upfront that all the passed instances are class method lookups
        foreach ($asynchronousInstances as $asynchronousInstance) {
            if (!($asynchronousInstance instanceof AsynchronousClassMethod))
                throw new WrongParametersException("Currently you can only supply AsynchronousClassMethod objects to the Http Loopback Asynchronous Processor");
        }

        // Create a class inspector for use below
        $classInspector = $this->classInspectorProvider->getClassInspector(AsynchronousClassMethod::class);

        // Grab the config parameters
        $loopbackHost = Configuration::readParameter("http.loopback.host");
        $authKey = $this->hashProvider->generateHash(Configuration::readParameter("internal.controller.secret"));


        // Generate requests array
        $requests = [];

        /**
         * Loop through and create requests to send off.
         *
         * @var AsynchronousClassMethod $asynchronousInstance
         */
        foreach ($asynchronousInstances as $asynchronousInstance) {


            // Create a loopback request
            $loopbackRequest = new HttpLoopbackRequest($asynchronousInstance->getClassName(), $asynchronousInstance->getMethodName(), $asynchronousInstance->getParameters(), $asynchronousInstance->getParameterTypes(), $asynchronousInstance->getReturnValueType(), $securableId, $securableType, $accountId);

            // Make the request
            $requests[] = new Request($loopbackHost . "/internal/callMethod", Request::METHOD_POST, [],
                $this->objectToJSONConverter->convert($loopbackRequest), new Headers(["AUTH-HASH" => $authKey]));


        }

        // Dispatch the requests
        $responses = $this->multiRequestDispatcher->dispatch($requests);

        // Loop through the responses and match up with asynchronous instances
        foreach ($responses as $index => $response) {
            $asynchronousInstance = $asynchronousInstances[$index];

            /**
             * @var HttpLoopbackResponse $loopbackResponse
             */
            $loopbackResponse = $this->objectBinder->bindFromArray(json_decode($response->getBody(), true), HttpLoopbackResponse::class);


            // Derive the status
            $status = $loopbackResponse->getStatus() == HttpLoopbackResponse::STATUS_SUCCESS ? Asynchronous::STATUS_COMPLETED : Asynchronous::STATUS_FAILED;

            // Set the status
            $classInspector->setPropertyData($asynchronousInstance, $status, "status", false);


            $result = $loopbackResponse->getReturnValue();
            if (is_array($result)) {
                $result = $this->objectBinder->bindFromArray($result, $loopbackResponse->getReturnValueType());
            }

            $classInspector->setPropertyData($asynchronousInstance, $result, "returnValue", false);


        }

        return $asynchronousInstances;

    }


}