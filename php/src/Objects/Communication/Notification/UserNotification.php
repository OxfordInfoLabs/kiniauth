<?php


namespace Kiniauth\Objects\Communication\Notification;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class UserNotification
 * @package Kiniauth\Objects\Communication\Notification
 *
 * @table ka_user_notification
 * @generate
 */
class UserNotification extends ActiveRecord {

    /**
     * @var integer
     * @primaryKey
     */
    private $notificationId;

    /**
     * @var integer
     * @primaryKey
     */
    private $userId;


    /**
     * @var boolean
     */
    private $read;


    /**
     * @var NotificationSummary
     * @manyToOne
     * @parentJoinColumns notification_id
     * @readOnly
     *
     */
    private $notification;

    /**
     * UserNotification constructor.
     *
     * @param int $notificationId
     * @param int $userId
     * @param bool $read
     */
    public function __construct($notificationId, $userId, $read) {
        $this->notificationId = $notificationId;
        $this->userId = $userId;
        $this->read = $read;
    }


    /**
     * @return int
     */
    public function getNotificationId() {
        return $this->notificationId;
    }

    /**
     * @param int $notificationId
     */
    public function setNotificationId($notificationId) {
        $this->notificationId = $notificationId;
    }

    /**
     * @return int
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId) {
        $this->userId = $userId;
    }

    /**
     * @return bool
     */
    public function isRead() {
        return $this->read;
    }

    /**
     * @param bool $read
     */
    public function setRead($read) {
        $this->read = $read;
    }

    /**
     * @return NotificationSummary
     */
    public function getNotification() {
        return $this->notification;
    }


}