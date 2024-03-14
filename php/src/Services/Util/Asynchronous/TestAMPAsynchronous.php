<?php


namespace Kiniauth\Services\Util\Asynchronous;


use Kinikit\Core\Asynchronous\Asynchronous;
use function Amp\delay;

class TestAMPAsynchronous extends Asynchronous {

    /** @var string */
    private $evaluatedProperty;

    /**
     * Construct this class
     *
     * TestAsynchronous constructor.
     */
    public function __construct(
        private string $name,
        private float $timeout = 0
    ) {
    }


    /**
     * Do a simple evaluation
     *
     * @return string
     */
    public function run() : string{
        delay($this->timeout);
        if ($this->name == "FAIL") {
            throw new \Exception("Failed");
        }
        $this->evaluatedProperty = "Evaluated: " . $this->name;

        return "Returned: " . $this->name;
    }

    public function getName() : string {
        return $this->name;
    }

    public function getEvaluatedProperty() : ?string {
        return $this->evaluatedProperty;
    }


}