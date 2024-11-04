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

        return new ProjectImportAnalysis($projectExport->getExportDateTime(),[
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
                $notificationGroup->setId(null);
                $this->notificationService->saveNotificationGroup($notificationGroup, $projectKey, $accountId);
            }
        }


    }


}