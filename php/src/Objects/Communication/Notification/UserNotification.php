<?php


namespace Kiniauth\Objects\Communication\Notification;

/**
 * Class UserNotification
 * @package Kiniauth\Objects\Communication\Notification
 *
 * @table ka_user_notification
 * @generate
 */
class UserNotification {

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