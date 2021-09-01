<?php


namespace Kiniauth\Objects\Workflow\Task\Scheduled;


use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class ScheduledTaskTimePeriod
 * @package Kiniauth\Objects\Workflow\Task\Scheduled
 *
 * @table ka_scheduled_task_time_period
 * @generate
 */
class ScheduledTaskTimePeriod extends ActiveRecord {

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $createdDate;

    /**
     * @var integer
     * @min 1
     * @max 28
     */
    private $dateInMonth;


    /**
     * @var integer
     * @min 1
     * @max 7
     */
    private $dayOfWeek;


    /**
     * @min 0
     * @max 23
     * @var integer
     */
    private $hour;


    /**
     * @var integer
     * @min 0
     * @max 59
     */
    private $minute;

    /**
     * AlertGroupTimePeriod constructor.
     * @param int $dateInMonth
     * @param int $dayOfWeek
     * @param int $hour
     * @param int $minute
     */
    public function __construct($dateInMonth = null, $dayOfWeek = null, $hour = null, $minute = null, $id = null) {
        $this->dateInMonth = $dateInMonth;
        $this->dayOfWeek = $dayOfWeek;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->id = $id;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedDate() {
        return $this->createdDate;
    }


    /**
     * @return int
     */
    public function getDateInMonth() {
        return is_numeric($this->dateInMonth) ? sprintf('%02d', $this->dateInMonth) : $this->dateInMonth;
    }

    /**
     * @param int $dateInMonth
     */
    public function setDateInMonth($dateInMonth) {
        $this->dateInMonth = $dateInMonth;
    }

    /**
     * @return int
     */
    public function getDayOfWeek() {
        return $this->dayOfWeek;
    }

    /**
     * @param int $dayOfWeek
     */
    public function setDayOfWeek($dayOfWeek) {
        $this->dayOfWeek = $dayOfWeek;
    }

    /**
     * @return int
     */
    public function getHour() {
        return is_numeric($this->hour) ? sprintf('%02d', $this->hour) : $this->hour;
    }

    /**
     * @param int $hour
     */
    public function setHour($hour) {
        $this->hour = $hour;
    }

    /**
     * @return int
     */
    public function getMinute() {
        return is_numeric($this->minute) ? sprintf('%02d', $this->minute) : $this->minute;
    }

    /**
     * @param int $minute
     */
    public function setMinute($minute) {
        $this->minute = $minute;
    }


    /**
     * Validate method
     */
    public function validate() {
        $validationErrors = [];

        if (is_numeric($this->dateInMonth) && is_numeric($this->dayOfWeek)) {
            $validationErrors["dateInMonth"] = new FieldValidationError("dateInMonth", "ambiguousperiod", "You cannot supply both a date in month and day of week for an alert group time period");
        }

        if ((is_numeric($this->dateInMonth) || is_numeric($this->dayOfWeek)) && !is_numeric($this->hour)) {
            $validationErrors["hour"] = new FieldValidationError("hour", "required", "You must supply an hour for an alert group time period if a date or day of month has been supplied");
        }

        if ((is_numeric($this->dateInMonth) || is_numeric($this->dayOfWeek) || is_numeric($this->hour)) && !is_numeric($this->minute)) {
            $validationErrors["minute"] = new FieldValidationError("minute", "required", "You must supply a minute for an alert group time period if any other time value has been set");
        }

        return $validationErrors;
    }

}