<?php

namespace Kiniauth\Services\ImportExport;

use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\ValueObjects\ImportExport\ExportableProjectResources;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;

class DefaultProjectExporter implements ProjectExporter {

    private $pkMappings = [
        "notificationGroups" => []
    ];


    /**
     * Construct with notification service
     *
     * @param NotificationService $notificationService
     */
    public function __construct(private NotificationService $notificationService) {
    }


    /**
     * Get exportable project resources
     *
     * @param int $accountId
     * @param string $projectKey
     * @return ExportableProjectResources
     */
    public function getExportableProjectResources(int $accountId, string $projectKey) {


        // Grab all notification groups
        $notificationGroups = array_map(function ($group) {
            return new ProjectExportResource($group->getId(), $group->getName());
        }, $this->notificationService->listNotificationGroups(PHP_INT_MAX, 0, $projectKey, $accountId));

        return new ExportableProjectResources([
            "notificationGroups" => $notificationGroups
        ]);


    }

    /**
     * Export a project
     *
     * @param int $accountId
     * @param string $projectKey
     * @param ProjectExportConfig $exportProjectConfig
     * @return ProjectExport
     */
    public function exportProject(int $accountId, string $projectKey, ProjectExportConfig $exportProjectConfig) {

        // Ids
        $notificationGroupIds = $exportProjectConfig->getIncludedNotificationGroupIds();

        // Grab all notification groups
        $includedNotificationGroupSummaries = array_filter($this->notificationService->listNotificationGroups(PHP_INT_MAX, 0, $projectKey, $accountId),
            function ($item) use ($notificationGroupIds) {
                if (in_array($item->getId(), $notificationGroupIds)) {
                    return true;
                }
            });

        // Included groups
        $includedNotificationGroups = array_map(function ($notificationGroupSummary) {
            return new NotificationGroup(new NotificationGroupSummary($notificationGroupSummary->getName(),
                [], $notificationGroupSummary->getCommunicationMethod(), $this->getNewPK("notificationGroups", $notificationGroupSummary->getId())
            ), null, null);
        }, $includedNotificationGroupSummaries);

        return new ProjectExport($exportProjectConfig, $includedNotificationGroups);

    }

    /**
     * Get next item pk
     *
     * @param $itemType
     * @return void
     */
    protected function getNewPK($itemType, $existingPK) {
        if (!isset($this->pkMappings[$itemType])) {
            $this->pkMappings[$itemType] = [];
        }

        $nextItemPk = sizeof($this->pkMappings[$itemType]) + 1;
        $this->pkMappings[$itemType]["PK" . $existingPK] = $nextItemPk;

        return $nextItemPk;
    }

}