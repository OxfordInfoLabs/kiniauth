<?php


namespace Kiniauth\Services\Communication\Notification;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Notification\Notification;
use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Communication\Notification\NotificationSummary;
use Kiniauth\Objects\Communication\Notification\UserNotification;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Communication\Notification\CommunicationMethod\NotificationCommunicationMethod;
use Kinikit\Core\DependencyInjection\Container;

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
        try {
            return NotificationGroup::fetch($notificationGroupId)->returnSummary();
        } catch (\Exception $e) {
            return null;
        }
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
     * Create a notification from a definition
     *
     * @param NotificationSummary $notification
     * @return integer
     */
    public function createNotification($notificationSummary, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Create a full Notification from the summary
        $notification = new Notification($notificationSummary, $projectKey, $accountId);
        $notification->save();


        /**
         * @var Notification $notification
         */
        $notification = Notification::fetch($notification->getId());


        // If this is a user based notification, create a user
        // notification entry
        $userIds = [];
        if ($notification->getUser()) {
            $userIds[$notification->getUser()->getId()] = 1;
        }

        // If notification groups, loop through each of these and identify any users
        if ($notification->getNotificationGroups()) {
            foreach ($notification->getNotificationGroups() as $notificationGroup) {
                foreach ($notificationGroup->getMembers() as $member) {
                    if ($member->getUser()) {
                        $userIds[$member->getUser()->getId()] = 1;
                    }
                }

                // If not internal communication, call the communication method with the group data
                if ($notificationGroup->getCommunicationMethod() !== NotificationGroupSummary::COMMUNICATION_METHOD_INTERNAL_ONLY) {
                    $commsMethod = Container::instance()->getInterfaceImplementationClass(NotificationCommunicationMethod::class, $notificationGroup->getCommunicationMethod());
                    if ($commsMethod) {
                        $commsMethod->processNotification($notification, $notificationGroup->getMembers());
                    }
                }

            }
        }

        // Store all entries for applicable users
        foreach (array_keys($userIds) as $userId) {
            $userNotification = new UserNotification($notification->getId(), $userId,
                $notification->getInitialState() == Notification::STATE_READ);
            $userNotification->save();
        }

        return $notification->getId();

    }

    /**
     * List notifications, default limited to the logged in account and user
     *
     * @param string $accountId
     * @param string $userId
     *
     * @return NotificationSummary
     */
    public function listNotifications($limit = 25, $offset = 0, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT, $userId = User::LOGGED_IN_USER) {

        $query = "WHERE userId = ? AND notification.account_id = ?";
        $params = [$userId, $accountId];

        if ($projectKey) {
            $query .= " AND notification.project_key = ?";
            $params[] = $projectKey;
        }

        $query .= " ORDER BY notificationId DESC LIMIT $limit OFFSET $offset";

        // Return a summary array
        return array_map(function ($instance) {
            return $instance->returnSummary();
        },
            UserNotification::filter($query, $params));


    }


    /**
     * Get unread notification count for a user (defaults to logged in user)
     *
     * @param string $userId
     */
    public function getUnreadUserNotificationCount($userId = User::LOGGED_IN_USER) {
        return UserNotification::values("COUNT(*)", "WHERE userId = ? AND NOT read", $userId)[0];
    }

    /**
     * Mark one or more user notifications as read / unread
     *
     * @param int[] $notificationIds
     * @param boolean $read
     * @param string $userId
     */
    public function markUserNotifications($notificationIds, $read = true, $userId = User::LOGGED_IN_USER) {

        // Create multi level pks
        $pks = array_map(function ($notificationId) use ($userId) {
            return [$notificationId, $userId];
        }, $notificationIds);

        // Grab matching notifications
        $matches = UserNotification::multiFetch($pks);
        foreach ($matches as $match) {
            $match->setRead($read);
            $match->save();
        }

    }



}
