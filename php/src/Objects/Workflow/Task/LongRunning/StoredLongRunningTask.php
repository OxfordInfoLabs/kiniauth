<?php


namespace Kiniauth\Objects\Workflow\Task\LongRunning;


use Kiniauth\Objects\Account\Account;

/**
 * Long running task -
 *
 * @table ka_long_running_task
 * @generate
 */
class StoredLongRunningTask extends StoredLongRunningTaskSummary {

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $accountId;

    /**
     * @var string
     */
    protected $projectKey;


    /**
     * Number of minutes to keep this task available after it finishes (either success or failure).
     *
     * @var integer
     */
    protected $expiryMinutes;

    /**
     * Timeout date
     *
     * @var \DateTime
     */
    protected $timeoutDate;


    // Status constants
    const STATUS_RUNNING = "RUNNING";
    const STATUS_COMPLETED = "COMPLETED";
    const STATUS_FAILED = "FAILED";
    const STATUS_TIMEOUT = "TIMEOUT";

    // Default to one week expiry
    const DEFAULT_TIMEOUT_SECONDS = 3600;
    const DEFAULT_EXPIRY_MINUTES = 10080;

    /**
     * LongRunningTask constructor.
     *
     * @param string $taskIdentifier
     * @param string $taskKey
     * @param int $accountId
     * @param string $projectKey
     * @param integer $expiryMinutesAfterCompletion
     */
    public function __construct($taskIdentifier, $taskKey, $expiryMinutes = self::DEFAULT_EXPIRY_MINUTES, $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $this->taskKey = $taskKey;
        $this->accountId = $accountId;
        $this->projectKey = $projectKey;
        $this->taskIdentifier = $taskIdentifier;

        $this->startedDate = new \DateTime();
        $this->expiryMinutes = $expiryMinutes;
        $this->timeoutDate = clone $this->startedDate;
        $this->timeoutDate->add(new \DateInterval("PT" . $timeoutSeconds . "S"));

        $this->status = self::STATUS_RUNNING;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }


    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     * @return string
     */
    public function getProjectKey() {
        return $this->projectKey;
    }

    /**
     * @return int
     */
    public function getExpiryMinutes() {
        return $this->expiryMinutes;
    }

    /**
     * @return \DateTime
     */
    public function getTimeoutDate() {
        return $this->timeoutDate;
    }


    /**
     * @param string $status
     */
    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     * @param \DateTime $finishedDate
     */
    public function setFinishedDate($finishedDate) {
        $this->finishedDate = $finishedDate;
    }


    /**
     * @param \DateTime $expiryDate
     */
    public function setExpiryDate($expiryDate) {
        $this->expiryDate = $expiryDate;
    }


    /**
     * @param mixed $progressData
     */
    public function setProgressData($progressData) {
        $this->progressData = $progressData;
    }

    /**
     * @param mixed $result
     */
    public function setResult($result) {
        $this->result = $result;
    }


}