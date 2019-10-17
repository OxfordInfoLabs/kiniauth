<?php

namespace Kiniauth\Test\Objects\Workflow;

use Kiniauth\Objects\Workflow\PendingAction;
use Kiniauth\Test\TestBase;


class PendingActionTest extends TestBase {


    public function testNewDefaultPendingActionsAreCreatedWithRandomIdentifierAnd24HrExpiry() {

        $pendingAction = new PendingAction(1, 3, "Test Action");

        $this->assertEquals(16, strlen($pendingAction->getIdentifier()));

        $now = new \DateTime();
        $now->add(new \DateInterval("P1D"));

        $this->assertEquals($now->format("d/m/Y"), $pendingAction->getExpiryDateTime()->format("d/m/Y"));
    }


    public function testCanSetCustomExpiryOffsetAndExplicitExpiryDates() {


        $pendingAction = new PendingAction("Test Action", 2, null, "P10Y");

        $now = new \DateTime();
        $now->add(new \DateInterval("P10Y"));

        $this->assertEquals($now->format("d/m/Y"), $pendingAction->getExpiryDateTime()->format("d/m/Y"));


        $pendingAction = new PendingAction("Test Action", 1, null, null,  $now);
        $this->assertEquals($now, $pendingAction->getExpiryDateTime());


    }


}
