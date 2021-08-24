<?php


namespace Kiniauth\Services\Communication\Notification\CommunicationMethod;


use Kiniauth\Objects\Communication\Notification\Notification;
use Kiniauth\Objects\Communication\Notification\NotificationGroupMember;
use Kiniauth\Objects\Communication\Notification\NotificationSummary;

/**
 * Main communication method interface
 *
 * @implementation email Kiniauth\Services\Communication\Notification\CommunicationMethod\EmailNotificationCommunicationMethod
 *
 */
interface NotificationCommunicationMethod {

    /**
     * Process a notification for this communication method using the supplied
     * group members.
     *
     * @param Notification $notification
     * @param NotificationGroupMember[] $groupMembers
     */
    public function processNotification($notification, $groupMembers);

}