<?php


namespace Kiniauth\Objects\Communication\Notification;

use Kiniauth\Traits\Account\AccountProject;

/**
 * Class Notification
 * @package Kiniauth\Objects\Communication\Notification
 *
 * @table ka_notification
 * @generate
 */
class Notification extends NotificationSummary {

    use AccountProject;

    /**
     * Notification constructor.
     *
     * @param NotificationSummary $notificationSummary
     * @param string $projectKey
     * @param integer $accountId
     */
    public function __construct($notificationSummary, $projectKey = null, $accountId = null) {

        if ($notificationSummary)
            parent::__construct($notificationSummary->getTitle(),
                $notificationSummary->getContent(),
                $notificationSummary->getUser(),
                $notificationSummary->getNotificationGroups(),
                $notificationSummary->getCategory(),
                $notificationSummary->getLevel(),
                $notificationSummary->getInitialState(),
                $notificationSummary->getCreatedDate() ? $notificationSummary->getCreatedDate() : new \DateTime());
        else
            $this->createdDate = new \DateTime();

        $this->projectKey = $projectKey;
        $this->accountId = $accountId;

    }


    /**
     * Return notification summary
     *
     * @return NotificationSummary
     */
    public function returnSummary() {
        return new NotificationSummary($this->getTitle(),
            $this->getContent(), $this->getUser(), $this->getNotificationGroups(),
            $this->getCategory(), $this->getLevel(), $this->getInitialState());
    }


}