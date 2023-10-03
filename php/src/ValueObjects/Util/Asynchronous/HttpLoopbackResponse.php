<?php

namespace Kiniauth\ValueObjects\Util\Asynchronous;

class HttpLoopbackResponse {

    /**
     * @var string
     */
    private $status;

    /**
     * @var mixed
     */
    private $returnValue;


    /**
     * @var string
     */
    private $returnValueType;

    // status constants
    const STATUS_SUCCESS = "Success";
    const STATUS_EXCEPTION = "Failure";


    /**
     * @param string $status
     * @param mixed $returnValue
     * @param string $returnValueType
     */
    public function __construct($status, $returnValue, $returnValueType) {
        $this->status = $status;
        $this->returnValue = $returnValue;
        $this->returnValueType = $returnValueType;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }


    /**
     * @return mixed
     */
    public function getReturnValue() {
        return $this->returnValue;
    }

    /**
     * @return string
     */
    public function getReturnValueType() {
        return $this->returnValueType;
    }


}