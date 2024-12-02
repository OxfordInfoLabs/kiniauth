<?php

namespace Kiniauth\Services\ImportExport\ImportExporters;

use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Util\ObjectArrayUtils;

class NotificationGroupImportExporter extends ImportExporter {

    /**
     * Constryct with notification service
     *
     * @param NotificationService $notificationService
     */
    public function __construct(private NotificationService $notificationService) {
    }

    public function getObjectTypeCollectionIdentifier() {
        return "notificationGroups";
    }

    public function getObjectTypeCollectionTitle() {
        return "Notification Groups";
    }

    public function getObjectTypeImportClassName() {
        return NotificationGroup::class;
    }

    public function getObjectTypeExportConfigClassName() {
        return ObjectInclusionExportConfig::class;
    }

    /**
     * Get array of project export resources for the supplied type
     *
     * @param int $accountId
     * @param string $projectKey
     *
     * @return ProjectExportResource[]
     */
    public function getExportableProjectResources(int $accountId, string $projectKey) {
        return array_map(function ($group) {
            return new ProjectExportResource($group->getId(), $group->getName(), new ObjectInclusionExportConfig(true));
        }, $this->notificationService->listNotificationGroups(PHP_INT_MAX, 0, $projectKey, $accountId));
    }


    /**
     * Create the export objects for this type
     *
     * @param int $accountId
     * @param string $projectKey
     * @param mixed $exportProjectConfig
     * @return mixed[]
     */
    public function createExportObjects(int $accountId, string $projectKey, mixed $exportProjectConfig) {


        // Grab all notification groups
        $includedNotificationGroupSummaries = array_filter($this->notificationService->listNotificationGroups(PHP_INT_MAX, 0, $projectKey, $accountId),
            function ($item) use ($exportProjectConfig) {
                return (($exportProjectConfig[$item->getId()] ?? null)?->isIncluded());
            });

        // Included groups
        return array_map(function ($notificationGroupSummary) {
            return new NotificationGroup(new NotificationGroupSummary($notificationGroupSummary->getName(),
                [], $notificationGroupSummary->getCommunicationMethod(), self::getNewExportPK("notificationGroups", $notificationGroupSummary->getId())
            ), null, null);
        }, $includedNotificationGroupSummaries);

    }

    /**
     * @param int $accountId
     * @param string $projectKey
     * @param ProjectExport $projectExport
     *
     *
     * @return ProjectImportResource[]
     */
    public function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $exportProjectConfig) {


        // Get existing notification groups by title
        $allExistingNotificationGroups = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $this->notificationService->listNotificationGroups(PHP_INT_MAX, 0,
            $projectKey, $accountId));

        // Loop through export notification groups, create import resources
        $importResources = [];
        foreach ($exportObjects as $notificationGroup) {
            $importResources[] = new ProjectImportResource($notificationGroup->getId(), $notificationGroup->getName(),
                isset($allExistingNotificationGroups[$notificationGroup->getName()]) ? ProjectImportResourceStatus::Ignore :
                    ProjectImportResourceStatus::Create);
        }

        return $importResources;
    }


    /**
     * Import project objects for this type using the supplied array of export objects and export project config
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $exportProjectConfig
     *
     * @return void
     */
    public function importObjects(int $accountId, string $projectKey, array $exportObjects, mixed $exportProjectConfig) {

        // Get existing notification groups by title
        $allExistingNotificationGroups = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $this->notificationService->listNotificationGroups(PHP_INT_MAX, 0,
            $projectKey, $accountId));


        // Loop through export notification groups, create new ones
        foreach ($exportObjects as $notificationGroup) {
            if (!isset($allExistingNotificationGroups[$notificationGroup->getName()])) {
                $prevId = $notificationGroup->getId();
                $notificationGroup->setId(null);
                $newId = $this->notificationService->saveNotificationGroup($notificationGroup, $projectKey, $accountId);
                self::setImportItemIdMapping("notificationGroups", $prevId, $newId);
            } else {
                self::setImportItemIdMapping("notificationGroups", $notificationGroup->getId(), $allExistingNotificationGroups[$notificationGroup->getName()]->getId());
            }
        }

    }


}