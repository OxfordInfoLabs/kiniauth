<?php

namespace Kiniauth\Services\ImportExport\ImportExporters;

use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\Services\Security\APIKeyService;
use Kiniauth\Services\Security\RoleService;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ExportObjects\ExportedApiKey;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kiniauth\ValueObjects\Security\ScopeObjectRolesAssignment;
use Kinikit\Core\Util\ObjectArrayUtils;

class APIKeyImportExporter extends ImportExporter {


    /**
     * Inject API key service into our import exporter.
     *
     * @param APIKeyService $APIKeyService
     */
    public function __construct(private APIKeyService $apiKeyService, private RoleService $roleService) {
    }

    public function getObjectTypeCollectionIdentifier() {
        return "apiKeys";
    }

    public function getObjectTypeCollectionTitle() {
        return "API Keys";
    }

    public function getObjectTypeImportClassName() {
        return ExportedApiKey::class;
    }

    public function getObjectTypeExportConfigClassName() {
        return ObjectInclusionExportConfig::class;
    }

    /**
     * Get exportable project API keys
     *
     * @param int $accountId
     * @param string $projectKey
     * @return ProjectExportResource[]
     */
    public function getExportableProjectResources(int $accountId, string $projectKey) {

        return array_map(function ($apiKey) {
            return new ProjectExportResource($apiKey->getId(), $apiKey->getDescription(), new ObjectInclusionExportConfig(true));
        }, $this->apiKeyService->listAPIKeys($projectKey, $accountId));


    }

    /**
     * Create export objects according to the passed export config
     *
     * @param int $accountId
     * @param string $projectKey
     * @param mixed $objectExportConfig
     * @param mixed $allProjectExportConfig
     * @return ExportedApiKey[]
     */
    public function createExportObjects(int $accountId, string $projectKey, mixed $objectExportConfig, mixed $allProjectExportConfig) {

        $apiKeys = $this->apiKeyService->listAPIKeys($projectKey, $accountId);

        $exportedItems = [];
        foreach ($apiKeys as $apiKey) {
            $exportConfig = $objectExportConfig[$apiKey->getId()] ?? null;
            if ($exportConfig?->isIncluded()) {
                $exportedItems[] = new ExportedApiKey(self::getNewExportPK("apiKeys", $apiKey->getId()), $apiKey->getDescription(),
                    ObjectArrayUtils::getMemberValueArrayForObjects("roleId", array_filter($apiKey->getRoles() ?? [], function ($role) {
                        return $role->getScope() == Role::SCOPE_PROJECT;
                    })));
            }
        }

        return $exportedItems;

    }

    /**
     * Analyse import based on existence of items in account.
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $objectExportConfig
     * @return void
     */
    public function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        $accountApiKeys = ObjectArrayUtils::indexArrayOfObjectsByMember("description", $this->apiKeyService->listAPIKeys($projectKey, $accountId));

        // Loop through each export object and analyse
        $analysis = [];
        foreach ($exportObjects as $exportObject) {
            $config = $objectExportConfig[$exportObject->getId()] ?? null;

            if ($config?->isIncluded()) {
                $description = $exportObject->getDescription();
                $analysis[] = new ProjectImportResource($exportObject->getId(), $description,
                    isset($accountApiKeys[$description]) ? ProjectImportResourceStatus::Update : ProjectImportResourceStatus::Create,
                    isset($accountApiKeys[$description]) ? $accountApiKeys[$description]->getId() : null);
            }
        }

        return $analysis;


    }

    /**
     * Import API keys
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $objectExportConfig
     *
     * @return void
     */
    public function importObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        $accountApiKeys = ObjectArrayUtils::indexArrayOfObjectsByMember("description", $this->apiKeyService->listAPIKeys($projectKey, $accountId));

        foreach ($exportObjects as $exportObject) {
            $config = $objectExportConfig[$exportObject->getId()] ?? null;
            if ($config?->isIncluded()) {
                $description = $exportObject->getDescription();

                // If key already exists with description, update it - else create new one
                if (isset($accountApiKeys[$description])) {
                    $apiKeyId = $accountApiKeys[$description]->getId();
                } else {
                    $apiKeyId = $this->apiKeyService->createAPIKeyForAccountAndProject($description, $projectKey, $accountId);
                }

                // Update the assigned scope object roles in both cases
                $this->roleService->updateAssignedScopeObjectRoles(Role::APPLIES_TO_API_KEY, $apiKeyId, [
                    new ScopeObjectRolesAssignment(Role::SCOPE_PROJECT, $projectKey, $exportObject->getProjectRoleIds())
                ], $accountId);
            }
        }

    }
}