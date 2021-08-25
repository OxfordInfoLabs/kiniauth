<?php


namespace Kiniauth\Objects\Communication\Notification;


class UserNotificationSummary extends NotificationSummary {

    /**
     * @var boolean
     */
    private $read;

    /**
     * UserNotificationSummary constructor.
     *
     * @param UserNotification $userNotification
     */
    public function __construct($userNotification = null) {
        if ($userNotification) {
            $notificationSummary = $userNotification->getNotification();
            parent::__construct($notificationSummary->getTitle(),
                $notificationSummary->getContent(), $notificationSummary->getUser(),
                $notificationSummary->getNotificationGroups(),
                $notificationSummary->getCategory(), $notificationSummary->getLevel(),
                $notificationSummary->getInitialState(), $notificationSummary->getId());
            $this->createdDate = $notificationSummary->getCreatedDate();

            $this->read = $userNotification->isRead();
        }
    }


    /**
     * @return bool
     */
    public function isRead() {
        return $this->read;
    }


}