<?php

namespace Kiniauth\Services\ImportExport\ImportExporters;

use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\Services\Security\KeyPairService;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ExportObjects\ExportedApiKey;
use Kiniauth\ValueObjects\ImportExport\ExportObjects\ExportedKeyPair;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kiniauth\ValueObjects\Security\ScopeObjectRolesAssignment;
use Kiniauth\ValueObjects\Util\LabelValue;
use Kinikit\Core\Util\ObjectArrayUtils;

class KeyPairImportExporter extends ImportExporter {

    /**
     * Key pair service
     *
     * @param KeyPairService $keyPairService
     */
    public function __construct(private KeyPairService $keyPairService) {
    }


    public function getObjectTypeCollectionIdentifier() {
        return "keyPairs";
    }

    public function getObjectTypeCollectionTitle() {
        return "Key Pairs";
    }

    public function getObjectTypeImportClassName() {
        return LabelValue::class;
    }

    public function getObjectTypeExportConfigClassName() {
        return ObjectInclusionExportConfig::class;
    }

    /**
     * Get exportable project resources for display on export screen
     *
     * @param int $accountId
     * @param string $projectKey
     * @return ProjectExportResource[]
     */
    public function getExportableProjectResources(int $accountId, string $projectKey) {
        return array_map(function ($keyPair) {
            return new ProjectExportResource($keyPair->getValue(), $keyPair->getLabel(), new ObjectInclusionExportConfig(true));
        }, $this->keyPairService->listKeyPairs($projectKey, $accountId));
    }

    public function createExportObjects(int $accountId, string $projectKey, mixed $objectExportConfig, mixed $allProjectExportConfig) {
        $keyPairs = $this->keyPairService->listKeyPairs($projectKey, $accountId);

        $exportedItems = [];
        foreach ($keyPairs as $keyPair) {
            $exportConfig = $objectExportConfig[$keyPair->getValue()] ?? null;
            if ($exportConfig?->isIncluded()) {
                $exportedItems[] = new LabelValue($keyPair->getLabel(), self::getNewExportPK("keyPairs", $keyPair->getValue()));
            }
        }

        return $exportedItems;

    }

    public function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        $accountKeyPairs = ObjectArrayUtils::indexArrayOfObjectsByMember("label", $this->keyPairService->listKeyPairs($projectKey, $accountId));

        // Loop through each export object and analyse
        $analysis = [];
        foreach ($exportObjects as $exportObject) {
            $config = $objectExportConfig[$exportObject->getValue()] ?? null;

            if ($config?->isIncluded()) {
                $description = $exportObject->getLabel();
                $analysis[] = new ProjectImportResource($exportObject->getValue(), $description,
                    isset($accountKeyPairs[$description]) ? ProjectImportResourceStatus::Update : ProjectImportResourceStatus::Create,
                    isset($accountKeyPairs[$description]) ? $accountKeyPairs[$description]->getValue() : null);
            }
        }

        return $analysis;
    }

    public function importObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        $accountKeyPairs = ObjectArrayUtils::indexArrayOfObjectsByMember("label", $this->keyPairService->listKeyPairs($projectKey, $accountId));

        foreach ($exportObjects as $exportObject) {
            $config = $objectExportConfig[$exportObject->getValue()] ?? null;
            if ($config?->isIncluded()) {
                $description = $exportObject->getLabel();
                // If key already exists with description, update it - else create new one
                if (!isset($accountKeyPairs[$description])) {
                    $this->keyPairService->generateKeyPair($description, $projectKey, $accountId);
                }
            }
        }

    }
}