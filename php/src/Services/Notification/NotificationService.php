<?php


namespace Kiniauth\Services\Notification;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Communication\Notification\NotificationSummary;
use Kiniauth\Objects\Security\User;

class NotificationService {


    /**
     * List notification groups
     *
     * @param int $limit
     * @param int $offset
     * @param string $projectKey
     * @param integer $accountId
     *
     * @return NotificationGroupSummary[]
     */
    public function listNotificationGroups($limit = 25, $offset = 0, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $query = "WHERE accountId = ?";
        $params = [$accountId];

        if ($projectKey) {
            $query .= " AND project_key = ?";
            $params[] = $projectKey;
        }

        $query .= " ORDER BY name LIMIT $limit OFFSET $offset";

        // Return a summary array
        return array_map(function ($instance) {
            return $instance->returnSummary();
        },
            NotificationGroup::filter($query, $params));


    }


    /**
     * Get a single group by id
     *
     * @param $notificationGroupId
     * @return NotificationGroupSummary
     */
    public function getNotificationGroup($notificationGroupId) {
        return NotificationGroup::fetch($notificationGroupId)->returnSummary();
    }

    /**
     * Save a notification group
     *
     * @param NotificationGroupSummary $notificationGroupSummary
     * @param string $projectKey
     * @param integer $accountId
     */
    public function saveNotificationGroup($notificationGroupSummary, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        // Create and save the new group
        $notificationGroup = new NotificationGroup($notificationGroupSummary, $projectKey, $accountId);
        $notificationGroup->save();

        return $notificationGroup->getId();
    }


    /**
     * Remove a notification group by id
     *
     * @param $notificationGroupId
     */
    public function removeNotificationGroup($notificationGroupId) {
        $group = NotificationGroup::fetch($notificationGroupId);
        $group->remove();
    }


    /**
     * List notifications, default limited to the logged in account and user
     *
     * @param string $accountId
     * @param string $userId
     *
     * @return NotificationSummary
     */
    public function listNotifications($accountId = Account::LOGGED_IN_ACCOUNT, $userId = User::LOGGED_IN_USER, $limit = 25, $offset = 0) {

    }


    /**
     * Create a notification from a definition
     *
     * @param $notification
     */
    public function createNotification($notification, $accountId = Account::LOGGED_IN_ACCOUNT) {

    }


    /**
     * Mark a user notification as read
     *
     * @param $notificationId
     * @param string $userId
     */
    public function markUserNotificationAsRead($notificationId, $userId = User::LOGGED_IN_USER) {

    }


}