<?php

namespace Kiniauth\ValueObjects\Util\Asynchronous;

class HttpLoopbackRequest {

    /**
     * @var string
     */
    private $className;


    /**
     * @var string
     */
    private $methodName;


    /**
     * @var mixed[string]
     */
    private $parameterValues;

    /**
     * @var string[string]
     */
    private $parameterValueTypes;

    /**
     * @var string
     */
    private $returnType;

    /**
     * @var integer
     */
    private $securableId;

    /**
     * @var string
     */
    private $securableType;


    /**
     * @param string $className
     * @param string $methodName
     * @param mixed[string] $parameterValues
     * @param string[string] $parameterValueTypes
     * @param string $returnType
     * @param integer $securableId
     * @param string $securableType
     */
    public function __construct($className, $methodName, $parameterValues, $parameterValueTypes, $returnType, $securableId = null, $securableType = "USER") {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->parameterValues = $parameterValues;
        $this->parameterValueTypes = $parameterValueTypes;
        $this->returnType = $returnType;
        $this->securableId = $securableId;
        $this->securableType = $securableType;
    }

    /**
     * @return string
     */
    public function getClassName() {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getMethodName() {
        return $this->methodName;
    }

    /**
     * @return mixed[string]
     */
    public function getParameterValues() {
        return $this->parameterValues;
    }

    /**
     * @return string[string]
     */
    public function getParameterValueTypes() {
        return $this->parameterValueTypes;
    }

    /**
     * @return string
     */
    public function getReturnType() {
        return $this->returnType;
    }

    /**
     * @return integer
     */
    public function getSecurableId() {
        return $this->securableId;
    }

    /**
     * @return string
     */
    public function getSecurableType() {
        return $this->securableType;
    }


}