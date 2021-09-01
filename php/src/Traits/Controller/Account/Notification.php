<?php


namespace Kiniauth\Traits\Controller\Account;


use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Services\Communication\Notification\NotificationService;

trait Notification {

    /**
     * @var NotificationService
     */
    private $notificationService;

    /**
     * Notification constructor.
     *
     * @param NotificationService $notificationService
     */
    public function __construct($notificationService) {
        $this->notificationService = $notificationService;
    }


    /**
     * Get notification group by id
     *
     * @http GET /group/$groupId
     *
     * @param integer $groupId
     * @return NotificationGroupSummary
     */
    public function getNotificationGroup($groupId) {
        return $this->notificationService->getNotificationGroup($groupId);
    }

    /**
     * List all notification groups for account optionally within a single project
     *
     * @http GET /group
     *
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     *
     * @return NotificationGroupSummary[]
     */
    public function listNotificationGroups($projectKey = null, $offset = 0, $limit = 25) {
        return $this->notificationService->listNotificationGroups($limit, $offset, $projectKey);
    }


    /**
     * Save a notification group
     *
     * @http POST /group
     *
     * @param NotificationGroupSummary $notificationGroupSummary
     * @param string $projectKey
     */
    public function saveNotificationGroup($notificationGroupSummary, $projectKey = null) {
        $this->notificationService->saveNotificationGroup($notificationGroupSummary, $projectKey);
    }

    /**
     * Remove a notification group by id
     *
     * @http DELETE /group/$id
     *
     * @param integer $id
     */
    public function removeNotificationGroup($id) {
        $this->notificationService->removeNotificationGroup($id);
    }


    /**
     * @http GET /
     *
     * @param string $projectKey
     * @param int $limit
     * @param int $offset
     */
    public function listUserNotifications($projectKey = null, $offset = 0, $limit = 25) {
        return $this->notificationService->listNotifications($limit, $offset, $projectKey);
    }

    /**
     * @http GET /item
     *
     * @param int $id
     */
    public function getUserNotification($id) {
        return $this->notificationService->getUserNotification($id);
    }


    /**
     * Get the count of unread user notifications
     *
     * @http GET /unreadCount
     *
     * @return integer
     */
    public function getUnreadUserNotificationCount() {
        return $this->notificationService->getUnreadUserNotificationCount();
    }


    /**
     * Mark array of notifications as read by id
     *
     * @http POST /markRead
     *
     * @param int[] $notificationIds
     */
    public function markNotificationsRead($notificationIds) {
        $this->notificationService->markUserNotifications($notificationIds, true);
    }


    /**
     * Mark array of notifications as unread by id
     *
     * @http POST /markUnread
     *
     * @param int[] $notificationIds
     */
    public function markNotificationsUnread($notificationIds) {
        $this->notificationService->markUserNotifications($notificationIds, false);
    }

}
