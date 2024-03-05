<?php


namespace Kiniauth\Services\Util\Asynchronous;


use Kinikit\Core\Asynchronous\Asynchronous;

class TestAMPAsynchronous extends Asynchronous {

    private string $evaluatedProperty;

    /**
     * Construct this class
     *
     * TestAsynchronous constructor.
     */
    public function __construct(
        private string $name
    ) {
    }


    /**
     * Do a simple evaluation
     *
     * @return string
     */
    public function run() : string{

        if ($this->name == "FAIL") {
            throw new \Exception("Failed");
        }
        $this->evaluatedProperty = "Evaluated: " . $this->name;

        return "Returned: " . $this->name;
    }

    public function getName() : string {
        return $this->name;
    }

    public function getEvaluatedProperty() : string {
        return $this->evaluatedProperty;
    }


}