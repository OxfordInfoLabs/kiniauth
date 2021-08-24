<?php


namespace Kiniauth\Services\Communication\Notification\CommunicationMethod;


use Kiniauth\Objects\Communication\Email\BrandedTemplatedEmail;
use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Objects\Communication\Notification\Notification;
use Kiniauth\Objects\Communication\Notification\NotificationGroupMember;
use Kiniauth\Services\Communication\Email\EmailService;

class EmailNotificationCommunicationMethod implements NotificationCommunicationMethod {


    /**
     * @var EmailService
     */
    private $emailService;


    /**
     * EmailNotificationCommunicationMethod constructor.
     *
     * @param EmailService $emailService
     */
    public function __construct($emailService) {
        $this->emailService = $emailService;
    }

    /**
     * Process this notification for email
     *
     * @param Notification $notification
     * @param NotificationGroupMember[] $groupMembers
     */
    public function processNotification($notification, $groupMembers) {

        /**
         * Loop through each group member and send appropriate email
         */
        foreach ($groupMembers as $groupMember) {
            if ($groupMember->getUser()) {
                $email = new UserTemplatedEmail($groupMember->getUser()->getId(), "notification/notification", [
                    "notification" => $notification
                ]);
                $this->emailService->send($email, $notification->getAccountId(), $groupMember->getUser()->getId());
            } else if ($groupMember->getMemberData()) {
                $email = new BrandedTemplatedEmail("notification/notification", [
                    "notification" => $notification
                ], $notification->getAccountId(), null, $groupMember->getMemberData());
                $this->emailService->send($email, $notification->getAccountId());
            }

        }

    }
}