<?php


namespace Kiniauth\Objects\Workflow\Task\Scheduled;


use Kiniauth\Traits\Account\AccountProject;

/**
 * Class ScheduledTask
 * @package Kiniauth\Objects\Workflow\Task\Scheduled
 *
 * @table ka_scheduled_task
 * @generate
 * @interceptor Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskInterceptor
 */
class ScheduledTask extends ScheduledTaskSummary {
    use AccountProject;

    /**
     * @var \DateTime
     */
    protected $lastStartTime;

    /**
     * @var \DateTime
     */
    protected $lastEndTime;


    /**
     * @var \DateTime
     */
    protected $nextStartTime;


    /**
     * @var \DateTime
     */
    protected $timeoutTime;


    /**
     * ScheduledTask constructor.
     * @param ScheduledTaskSummary $scheduledTaskSummary
     * @param string $projectKey
     * @param integer $accountId
     */
    public function __construct($scheduledTaskSummary, $projectKey = null, $accountId = null) {
        if ($scheduledTaskSummary) {

            parent::__construct($scheduledTaskSummary->getTaskIdentifier(),
                $scheduledTaskSummary->getDescription(),
                $scheduledTaskSummary->getConfiguration(),
                $scheduledTaskSummary->getTimePeriods(),
                $scheduledTaskSummary->getStatus(),
                $scheduledTaskSummary->getNextStartTime() ? date_create_from_format("Y-m-d H:i:s", $scheduledTaskSummary->getNextStartTime()) : null,
                $scheduledTaskSummary->getLastStartTime() ? date_create_from_format("Y-m-d H:i:s", $scheduledTaskSummary->getLastStartTime()) : null,
                $scheduledTaskSummary->getLastEndTime() ? date_create_from_format("Y-m-d H:i:s", $scheduledTaskSummary->getLastEndTime()) : null,
                $scheduledTaskSummary->getTimeoutTime() ? date_create_from_format("Y-m-d H:i:s", $scheduledTaskSummary->getTimeoutTime()) : null,
                $scheduledTaskSummary->getTimeoutSeconds(),
                $scheduledTaskSummary->getId(),
                $scheduledTaskSummary->getTaskGroup(),
                $scheduledTaskSummary->getPid()
            );
        }
        $this->projectKey = $projectKey;
        $this->accountId = $accountId;
    }

    /**
     * @return \DateTime
     */
    public function getLastStartTime() {
        return $this->lastStartTime;
    }

    /**
     * @param \DateTime $lastStartTime
     */
    public function setLastStartTime($lastStartTime) {
        $this->lastStartTime = $lastStartTime;
    }

    /**
     * @return \DateTime
     */
    public function getLastEndTime() {
        return $this->lastEndTime;
    }

    /**
     * @param \DateTime $lastEndTime
     */
    public function setLastEndTime($lastEndTime) {
        $this->lastEndTime = $lastEndTime;
    }

    /**
     * @return \DateTime
     */
    public function getNextStartTime() {
        return $this->nextStartTime;
    }

    /**
     * @param \DateTime $nextStartTime
     */
    public function setNextStartTime($nextStartTime) {
        $this->nextStartTime = $nextStartTime;
    }

    /**
     * @return \DateTime
     */
    public function getTimeoutTime() {
        return $this->timeoutTime;
    }

    /**
     * @param \DateTime $timeoutTime
     */
    public function setTimeoutTime($timeoutTime) {
        $this->timeoutTime = $timeoutTime;
    }


    /**
     * Return a summary object
     *
     * @return ScheduledTaskSummary
     */
    public function returnSummary() {
        return new ScheduledTaskSummary($this->taskIdentifier, $this->description, $this->configuration,
            $this->timePeriods, $this->status,
            $this->nextStartTime ? $this->nextStartTime->format("Y-m-d H:i:s") : null,
            $this->lastStartTime ? $this->lastStartTime->format("Y-m-d H:i:s") : null,
            $this->lastEndTime ? $this->lastEndTime->format("Y-m-d H:i:s") : null,
            $this->timeoutTime ? $this->timeoutTime->format("Y-m-d H:i:s") : null,
            $this->timeoutSeconds, $this->id, $this->taskGroup, $this->pid);
    }

    /**
     * Recalculate the next start time using time periods
     */
    public function recalculateNextStartTime() {

        $currentDate = new \DateTime();

        $nextStartTime = null;
        // Loop through each time period and calculate the next start date
        foreach ($this->getTimePeriods() ?? [] as $timePeriod) {

            $startTime = null;

            // If a month based time period,
            if ($timePeriod->getDateInMonth()) {
                $startTimeString = $timePeriod->getDateInMonth() . "/" . $currentDate->format("m") . "/" . $currentDate->format("Y") . " " .
                    $timePeriod->getHour() . ":" . $timePeriod->getMinute();

                $startTime = date_create_from_format("d/m/Y H:i", $startTimeString);

                if ($startTime < $currentDate) {
                    $startTime->add(new \DateInterval("P1M"));
                }

            } else if ($timePeriod->getDayOfWeek()) {
                $currentDayOfWeek = date("N");
                $newInterval = $timePeriod->getDayOfWeek() - $currentDayOfWeek;
                if ($newInterval < 0)
                    $newInterval += 7;

                // Create a time based around todays date
                $startTime = date_create_from_format("d/m/Y H:i",
                    $currentDate->format("d/m/Y") . " " . $timePeriod->getHour() . ":" . $timePeriod->getMinute());

                // Move to the next day of week
                $startTime->add(new \DateInterval("P" . $newInterval . "D"));

                // Handle the case where day of week is today but earlier
                if ($startTime < $currentDate) {
                    $startTime->add(new \DateInterval("P1W"));
                }

            } else if (is_numeric($timePeriod->getHour())) {

                $startTime = date_create_from_format("d/m/Y H:i",
                    date("d/m/Y") . " " . $timePeriod->getHour() . ":" . $timePeriod->getMinute());


                // Handle the case where day of week is today but earlier
                if ($startTime < $currentDate) {
                    $startTime->add(new \DateInterval("P1D"));
                }
            } else if (is_numeric($timePeriod->getMinute())) {
                $startTime = date_create_from_format("d/m/Y H:i",
                    date("d/m/Y H") . ":" . $timePeriod->getMinute());

                // Handle the case where day of week is today but earlier
                if ($startTime < $currentDate) {
                    $startTime->add(new \DateInterval("PT1H"));
                }
            } else {
                $startTime = new \DateTime();
                $startTime->add(new \DateInterval("PT1M"));
            }


            if ($startTime) {

                if ($nextStartTime == null || $nextStartTime > $startTime)
                    $nextStartTime = $startTime;
            }
        }


        $this->nextStartTime = $nextStartTime;

    }


}