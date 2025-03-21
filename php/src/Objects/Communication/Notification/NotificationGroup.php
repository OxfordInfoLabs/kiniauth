<?php


namespace Kiniauth\Objects\Communication\Notification;

use Kiniauth\Traits\Account\AccountProject;

/**
 * Class NotificationGroup
 * @package Kiniauth\Objects\Communication\Notification
 *
 *
 * @table ka_notification_group
 * @generate
 */
class NotificationGroup extends NotificationGroupSummary {
    use AccountProject;

    /**
     * Create new notification group
     *
     * NotificationGroup constructor.
     *
     * @param NotificationGroupSummary $notificationGroupSummary
     * @param string $projectKey
     * @param integer $accountId
     *
     */
    public function __construct($notificationGroupSummary, $projectKey, $accountId, $accountSummary = null) {

        if ($notificationGroupSummary)
            parent::__construct($notificationGroupSummary->getName(), $notificationGroupSummary->getMembers(),
                $notificationGroupSummary->getCommunicationMethod(), $notificationGroupSummary->getId());

        $this->projectKey = $projectKey;
        $this->accountId = $accountId;
        $this->accountSummary = $accountSummary;
    }



    public function returnSummary() {
        return new NotificationGroupSummary($this->name, $this->members, $this->communicationMethod, $this->id);
    }
}