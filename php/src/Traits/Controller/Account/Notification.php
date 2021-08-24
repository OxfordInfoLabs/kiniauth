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
}
