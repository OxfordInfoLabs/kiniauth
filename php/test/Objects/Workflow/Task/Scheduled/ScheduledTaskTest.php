<?php


namespace Kiniauth\Test\Objects\Workflow\Task\Scheduled;


use Kiniauth\Objects\Workflow\Task\Scheduled\LatestScheduledTaskLog;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskLog;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;

include_once "autoloader.php";

class ScheduledTaskTest extends TestBase {


    public function testCanRecalculateNextStartTimeForDateBasedTimePeriod() {

        $month = (new \DateTime())->add(new \DateInterval("P1M"))->format("m");
        $year = (new \DateTime())->add(new \DateInterval("P1M"))->format("Y");

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("test", "Test Task",
            [], [
                new ScheduledTaskTimePeriod(1, null, 5, 30)]));

        $this->assertNull($scheduledTask->getNextStartTime());
        $scheduledTask->recalculateNextStartTime();

        $this->assertEquals("01/$month/$year 05:30:00", $scheduledTask->getNextStartTime()->format("d/m/Y H:i:s"));


        $currentTime = new \DateTime();
        $currentTime->add(new \DateInterval("PT1M"));
        $month = $currentTime->format("m");
        $date = $currentTime->format("d");
        $hour = $currentTime->format("H");
        $minute = $currentTime->format("i");
        $year = $currentTime->format("Y");


        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("test", "Test Task",
            [], [
                new ScheduledTaskTimePeriod($date, null, $hour, $minute)]));

        $this->assertNull($scheduledTask->getNextStartTime());
        $scheduledTask->recalculateNextStartTime();

        $this->assertEquals("$date/$month/$year $hour:$minute:00", $scheduledTask->getNextStartTime()->format("d/m/Y H:i:s"));


    }

    public function testCanRecalculateNextStartTimeForWeekDayBasedTimePeriod() {

        $daySub = date("N") - 1;

        $expectedDate = (new \DateTime())->add(new \DateInterval("P1W"))->sub(new \DateInterval("P" . $daySub . "D"));


        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("test", "Test Task",
            [], [
                new ScheduledTaskTimePeriod(null, 1, 5, 30)]));

        $this->assertNull($scheduledTask->getNextStartTime());
        $scheduledTask->recalculateNextStartTime();

        $this->assertEquals($expectedDate->format("d/m/Y") . " 05:30:00", $scheduledTask->getNextStartTime()->format("d/m/Y H:i:s"));


    }

    public function testCanRecalculateNextStartTimeForDailyTimePeriod() {

        $expectedDate = new \DateTime();
        $expectedDate->add(new \DateInterval("P1D"));

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("test", "Test Task",
            [], [
                new ScheduledTaskTimePeriod(null, null, 5, 30)]));

        $this->assertNull($scheduledTask->getNextStartTime());
        $scheduledTask->recalculateNextStartTime();

        $this->assertEquals($expectedDate->format("d/m/Y") . " 05:30:00", $scheduledTask->getNextStartTime()->format("d/m/Y H:i:s"));


    }


    public function testCanRecalculateNextStartTimeForHourlyPeriod() {

        $expectedHour = (new \DateTime())->format("H");
        $expectedDate = date_create_from_format("d/m/Y H:i", date("d/m/Y") . " $expectedHour:30");
        if ($expectedDate < new \DateTime())
            $expectedDate->add(new \DateInterval("PT1H"));

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("test", "Test Task",
            [], [
                new ScheduledTaskTimePeriod(null, null, null, 30)]));

        $this->assertNull($scheduledTask->getNextStartTime());
        $scheduledTask->recalculateNextStartTime();

        $this->assertEquals($expectedDate->format("d/m/Y H:i:s"), $scheduledTask->getNextStartTime()->format("d/m/Y H:i:s"));

    }

    public function testCanRecalculateNextStartTimeForEveryMinute() {

        $expectedDate = new \DateTime();
        $expectedDate->add(new \DateInterval("PT1M"));

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("test", "Test Task",
            [], [
                new ScheduledTaskTimePeriod(null, null, null, null)]));

        $this->assertNull($scheduledTask->getNextStartTime());
        $scheduledTask->recalculateNextStartTime();

        $this->assertEquals($expectedDate->format("d/m/Y H:i:s"), $scheduledTask->getNextStartTime()->format("d/m/Y H:i:s"));

    }


    public function testLastLogIsCorrectlyAttachedAndReturnedOnGettingScheduledTask() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $scheduledTask = new ScheduledTask(new ScheduledTaskSummary("test", "Test Task",
            [], [
                new ScheduledTaskTimePeriod(null, null, null, null)]), null, 1);

        $scheduledTask->save();

        $log = new ScheduledTaskLog($scheduledTask->getId(), new \DateTime(), new \DateTime(), "COMPLETED", "");
        $log->save();

        $log2 = new ScheduledTaskLog($scheduledTask->getId(), new \DateTime(), new \DateTime(), "FAILED", "Bad Task Run");
        $log2->save();

        $scheduledTask = ScheduledTask::fetch($scheduledTask->getId());
        $this->assertEquals("Test Task", $scheduledTask->getDescription());

        $latestLog = $scheduledTask->getLatestLog();
        $this->assertInstanceOf(LatestScheduledTaskLog::class, $latestLog);
        $this->assertEquals("FAILED", $latestLog->getStatus());
        $this->assertEquals("Bad Task Run", $latestLog->getLogOutput());

    }


}