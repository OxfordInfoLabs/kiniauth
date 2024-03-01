<?php

namespace Kiniauth\Test\Services\Workflow;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Workflow\DueWorkflowStepsTask;
use Kiniauth\Services\Workflow\ObjectWorkflowService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;

include_once "autoloader.php";

class DueWorkflowStepsTaskTest extends TestBase {

    /**
     * @var DueWorkflowStepsTask
     */
    private $task;

    /**
     * @var MockObject
     */
    private $service;

    public function setUp(): void {
        $this->service = MockObjectProvider::instance()->getMockInstance(ObjectWorkflowService::class);
        $this->task = new DueWorkflowStepsTask($this->service);
    }


    public function testWorkflowServiceCalledForAllPassedClassesInConfig() {

        $this->task->run(["classes" => [Account::class, User::class]]);

        $this->assertEquals(2, sizeof($this->service->getMethodCallHistory("processDueWorkflowStepsForObjectClass")));

        $this->assertTrue($this->service->methodWasCalled("processDueWorkflowStepsForObjectClass", [Account::class]));
        $this->assertTrue($this->service->methodWasCalled("processDueWorkflowStepsForObjectClass", [User::class]));


    }

}