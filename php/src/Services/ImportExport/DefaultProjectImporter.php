<?php

namespace Kiniauth\Services\ImportExport;

use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectImportAnalysis;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\ObjectArrayUtils;

class DefaultProjectImporter implements ProjectImporter {

    /**
     * @var array
     */
    private array $importItemIdMap = [];

    // Item types
    const ITEM_TYPE_NOTIFICATION_GROUP = "notificationGroup";


    /**
     * Construct with notification service
     *
     * @param NotificationService $notificationService
     */
    public function __construct(private NotificationService $notificationService) {
    }


    /**
     * Return import analysis for a project based on a project export
     *
     * @param int $accountId
     * @param string $projectKey
     * @param ProjectExport $projectExport
     *
     * @return ProjectImportAnalysis
     */
    public function analyseImport(int $accountId, string $projectKey, ProjectExport $projectExport) {

        // Get existing notification groups by title
        $allExistingNotificationGroups = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $this->notificationService->listNotificationGroups(PHP_INT_MAX, 0,
            $projectKey, $accountId));

        // Loop through export notification groups, create import resources
        $importResources = [];
        foreach ($projectExport->getNotificationGroups() as $notificationGroup) {
            $importResources[] = new ProjectImportResource($notificationGroup->getId(), $notificationGroup->getName(),
                isset($allExistingNotificationGroups[$notificationGroup->getName()]) ? ProjectImportResourceStatus::Ignore :
                    ProjectImportResourceStatus::Create);
        }

        return new ProjectImportAnalysis($projectExport->getExportDateTime(), [
            "Notification Groups" => $importResources]);

    }


    /**
     * Import a project from a project export into the supplied account and project
     *
     * @param int $accountId
     * @param string $projectKey
     * @param ProjectExport $projectExport
     *
     */
    public function importProject(int $accountId, string $projectKey, ProjectExport $projectExport) {

        // Get existing notification groups by title
        $allExistingNotificationGroups = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $this->notificationService->listNotificationGroups(PHP_INT_MAX, 0,
            $projectKey, $accountId));


        // Loop through export notification groups, create new ones
        foreach ($projectExport->getNotificationGroups() as $notificationGroup) {
            if (!isset($allExistingNotificationGroups[$notificationGroup->getName()])) {
                $prevId = $notificationGroup->getId();
                $notificationGroup->setId(null);
                $newId = $this->notificationService->saveNotificationGroup($notificationGroup, $projectKey, $accountId);
                $this->setImportItemIdMapping(self::ITEM_TYPE_NOTIFICATION_GROUP, $prevId, $newId);
            }
        }


    }


    /**
     * Set a mapping from an imported item id to a new one
     *
     * @param string $itemType
     * @param mixed $importId
     * @param mixed $newId
     *
     * @return void
     */
    protected function setImportItemIdMapping($itemType, $importId, $newId) {
        if (!isset($this->importItemIdMap[$itemType])) {
            $this->importItemIdMap[$itemType] = [];
        }
        $this->importItemIdMap[$itemType][$importId] = $newId;
    }

    /**
     * If a stored mapping for an item use it, otherwise use the passed value
     *
     * @param $itemType
     * @param $importId
     *
     * @return mixed
     */
    protected function remapImportedItemId($itemType, $importId) {
        return $this->importItemIdMap[$itemType][$importId] ?? $importId;
    }


}