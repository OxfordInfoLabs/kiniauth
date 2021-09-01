<?php


namespace Kiniauth\Test\Objects\Workflow\Task\Scheduled;


use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Test\TestBase;

include_once "autoloader.php";

class ScheduledTaskTimePeriodTest extends TestBase {


    public function testOnlyValidTimePeriodCombinationsAcceptedOnValidate() {

        $alert = new ScheduledTaskTimePeriod(12, 3);
        $validationErrors = $alert->validate();
        $this->assertEquals(3, sizeof($validationErrors));

        $alert = new ScheduledTaskTimePeriod(12, null);
        $validationErrors = $alert->validate();
        $this->assertEquals(2, sizeof($validationErrors));

        $alert = new ScheduledTaskTimePeriod(null, 3);
        $validationErrors = $alert->validate();
        $this->assertEquals(2, sizeof($validationErrors));

        $alert = new ScheduledTaskTimePeriod(null, 3, 12);
        $validationErrors = $alert->validate();
        $this->assertEquals(1, sizeof($validationErrors));

        $alert = new ScheduledTaskTimePeriod(12, null, 12);
        $validationErrors = $alert->validate();
        $this->assertEquals(1, sizeof($validationErrors));


    }

}