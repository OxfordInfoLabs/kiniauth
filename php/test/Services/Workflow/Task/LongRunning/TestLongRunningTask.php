<?php


namespace Kiniauth\Test\Services\Workflow\Task\LongRunning;


use Kiniauth\Services\Workflow\Task\LongRunning\LongRunningTask;

class TestLongRunningTask extends LongRunningTask {

    private $succeed;

    public function __construct($succeed = true) {
        $this->succeed = $succeed;
    }


    public function run() {
        if ($this->succeed)
            return "SUCCESS";
        else
            throw new \Exception("Long running task failure");
    }
}