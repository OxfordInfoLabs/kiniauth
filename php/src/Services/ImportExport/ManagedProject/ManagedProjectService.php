<?php

namespace Kiniauth\Services\ImportExport\ManagedProject;

use Kiniauth\Objects\Account\ProjectSummary;
use Kiniauth\Objects\ImportExport\ManagedProject\ManagedProject;
use Kiniauth\Objects\ImportExport\ManagedProject\ManagedProjectSummary;
use Kiniauth\Objects\ImportExport\ManagedProject\ManagedProjectTargetAccount;
use Kiniauth\Objects\ImportExport\ManagedProject\ManagedProjectVersion;
use Kiniauth\Services\Account\ProjectService;
use Kiniauth\Services\ImportExport\ImportExportService;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

class ManagedProjectService {

    private ImportExportService $importExportService;

    private ProjectService $projectService;

    /**
     * @param ImportExportService $importExportService
     * @param ProjectService $projectService
     */
    public function __construct($importExportService, $projectService) {
        $this->importExportService = $importExportService;
        $this->projectService = $projectService;
    }

    /**
     * @param int $id
     * @return ManagedProject
     */
    public function getManagedProject(int $id): ManagedProject {
        return ManagedProject::fetch($id);
    }

    /**
     * @return ManagedProject[]
     */
    public function getAll(): array {
        return ManagedProject::filter();
    }

    /**
     * @param int $accountId
     * @return ManagedProjectSummary[]
     */
    public function getManagedProjectsForAccount(int $accountId): array {

        $targetAccounts = ManagedProjectTargetAccount::filter("WHERE target_account_id = ?", $accountId);

        $managedProjectIds = array_map(fn ($targetAccount) => $targetAccount->getManagedProjectId(), $targetAccounts);

        return ManagedProjectSummary::multiFetch($managedProjectIds);
    }

    /**
     * @param int $accountId
     * @param string $projectKey
     * @return ManagedProject[]
     */
    public function searchForManagedProjects(?int $accountId = null, ?string $projectKey = null): array {

        $whereClauses = [];
        $params = [];
        if ($accountId) {
            $whereClauses[] = "source_account_id = ?";
            $params[] = $accountId;
        }
        if ($projectKey !== null) {
            $whereClauses[] = "source_project_key = ?";
            $params[] = $projectKey;
        }

        $query = (count($whereClauses) ? "WHERE " : "") . implode(" AND ", $whereClauses) . " ORDER BY name";

        return ManagedProject::filter($query, $params);

    }

    /**
     * @param string $name
     * @param int $accountId
     * @param string $projectKey
     * @return int
     */
    public function createManagedProject(string $name, int $accountId, string $projectKey): int {

        /** @var ManagedProject $managedProject */
        $managedProject = Container::instance()->new(ManagedProject::class);

        $managedProject->setName($name);
        $managedProject->setSourceAccountId($accountId);
        $managedProject->setSourceProjectKey($projectKey);

        $managedProject->save();

        return $managedProject->getId();

    }

    /**
     * @param int $id
     * @return int
     */
    public function exportAndUpdate(int $id): int {

        $managedProject = $this->getManagedProject($id);

        $exportProjectConfig = $managedProject->getExportConfig();

        $projectExport = $this->importExportService->exportProject($managedProject->getSourceProjectKey(), $exportProjectConfig, $managedProject->getSourceAccountId());

        $version = new ManagedProjectVersion($id, $projectExport, new \DateTime());
        $version->save();

        $this->issueProjectUpdate($version->getId());

        return $version->getId();

    }

    /**
     * @param int $versionId
     * @return void
     */
    public function issueProjectUpdate(int $versionId): void {

        /** @var ManagedProjectVersion $version */
        $version = ManagedProjectVersion::fetch($versionId);

        $managedProject = $this->getManagedProject($version->getManagedProjectId());
        $projectExport = $version->getProjectExport();

        $targetAccounts = $managedProject->getTargetAccounts();

        foreach ($targetAccounts as $targetAccount) {
            $this->importExportService->importProject($managedProject->getSourceProjectKey(), $projectExport, $targetAccount->getTargetAccountId());
        }

    }

    /**
     * @param int $managedProjectId
     * @param int $accountId
     * @return void
     */
    public function installProjectOnAccount(int $managedProjectId, int $accountId): void {

        $managedProject = $this->getManagedProject($managedProjectId);
        $projectKey = $managedProject->getSourceProjectKey();

        // Create project if not exists
        try {
            $this->projectService->getProject($projectKey, $accountId);
        } catch (ObjectNotFoundException) {
            $projectSummary = new ProjectSummary(
                ucfirst($projectKey),
                ucfirst($projectKey),
                $projectKey
            );

            $this->projectService->saveProject($projectSummary, $accountId);
        }

        $projectExport = $this->getLatestExport($managedProjectId);

        $this->importExportService->importProject($projectKey, $projectExport, $accountId);

        $this->addNewTargetAccount($managedProject, $accountId);
    }

    /**
     * @param int $id
     * @return ProjectExport|null
     */
    public function getLatestExport(int $id): ?ProjectExport {
        /** @var ManagedProjectVersion[] $orderedVersions */
        $orderedVersions = ManagedProjectVersion::filter("WHERE managed_project_id = ? ORDER BY export_date DESC", $id);

        $latestVersion = $orderedVersions[0] ?? null;
        return $latestVersion?->getProjectExport();
    }

    /**
     * @param ManagedProject $id
     * @param int $targetAccountId
     * @return void
     */
    public function addNewTargetAccount(ManagedProject $managedProject, int $targetAccountId): void {

        $targetAccounts = $managedProject->getTargetAccounts();
        $targetAccounts[] = new ManagedProjectTargetAccount($managedProject->getId(), $targetAccountId);

        $managedProject->setTargetAccounts($targetAccounts);
        $managedProject->save();

    }

}