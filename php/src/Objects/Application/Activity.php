<?php


namespace Kiniauth\Objects\Application;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class Activity
 *
 * @table ka_activity
 * @generate
 * @package Kiniauth\Objects\Application
 */
class Activity extends ActiveRecord {

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $timestamp;

    /**
     * @var integer
     */
    private $userId;


    /**
     * @var integer
     */
    private $accountId;


    /**
     * @var string
     */
    private $event;


    /**
     * @var integer
     */
    private $associatedObjectId;


    /**
     * @var string
     */
    private $associatedObjectDescription;

    /**
     * @var mixed[]
     * @json
     * @sqlType LONGTEXT
     */
    private $data;


    /**
     * @var string
     */
    private $loggedInSecurableType;

    /**
     * Logged in securable id
     *
     * @var integer
     */
    private $loggedInSecurableId;

    /**
     * @var string
     */
    private $transactionId;


    /**
     * Activity constructor.
     *
     * @param int $userId
     * @param int $accountId
     * @param string $event
     * @param int $associatedObjectId
     * @param string $associatedObjectDescription
     * @param mixed[] $data
     * @param $loggedInSecurableType
     * @param int $loggedInSecurableId
     * @param string $transactionId
     */
    public function __construct($userId, $accountId, $event, $associatedObjectId, $associatedObjectDescription, $data, $loggedInSecurableType, $loggedInSecurableId, $transactionId = null) {
        $this->userId = $userId;
        $this->accountId = $accountId;
        $this->event = $event;
        $this->associatedObjectId = $associatedObjectId;
        $this->associatedObjectDescription = $associatedObjectDescription;
        $this->data = $data;
        $this->timestamp = date("Y-m-d H:i:s");
        $this->loggedInSecurableType = $loggedInSecurableType;
        $this->loggedInSecurableId = $loggedInSecurableId;
        $this->transactionId = $transactionId;
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
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * @return int
     */
    public function getUserId() {
        return $this->userId;
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
    public function getEvent() {
        return $this->event;
    }

    /**
     * @return int
     */
    public function getAssociatedObjectId() {
        return $this->associatedObjectId;
    }

    /**
     * @return string
     */
    public function getAssociatedObjectDescription() {
        return $this->associatedObjectDescription;
    }

    /**
     * @return mixed[]
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getLoggedInSecurableType() {
        return $this->loggedInSecurableType;
    }

    /**
     * @return int
     */
    public function getLoggedInSecurableId() {
        return $this->loggedInSecurableId;
    }


    /**
     * @return string
     */
    public function getTransactionId() {
        return $this->transactionId;
    }


}
