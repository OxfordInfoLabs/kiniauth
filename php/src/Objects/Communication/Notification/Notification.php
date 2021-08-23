<?php


namespace Kiniauth\Objects\Communication\Notification;

use Kiniauth\Objects\Security\UserLabel;

/**
 * Class Notification
 * @package Kiniauth\Objects\Communication\Notification
 *
 * @table ka_notification
 * @generate
 */
class Notification extends NotificationSummary {


    /**
     * @var NotificationGroup[]
     * @manyToMany
     * @linkTable ka_notification_assigned_group
     */
    private $notificationGroups;


    /**
     * @var UserLabel
     * @manyToOne
     * @parentJoinColumns user_id
     */
    private $user;


    /**
     * @param \DateTime $createdDate
     */
    public function setCreatedDate($createdDate) {
        $this->createdDate = $createdDate;
    }

    /**
     * @param int $accountId
     */
    public function setAccountId($accountId) {
        $this->accountId = $accountId;
    }


    /**
     * @param NotificationLevel $level
     */
    public function setLevel($level) {
        $this->level = $level;
    }

    /**
     * @return NotificationGroup[]
     */
    public function getNotificationGroups() {
        return $this->notificationGroups;
    }

    /**
     * @param NotificationGroup[] $notificationGroups
     */
    public function setNotificationGroups($notificationGroups) {
        $this->notificationGroups = $notificationGroups;
    }

    /**
     * @return UserLabel
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param UserLabel $user
     */
    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @param string $content
     */
    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * @param string $initialState
     */
    public function setInitialState($initialState) {
        $this->initialState = $initialState;
    }


}