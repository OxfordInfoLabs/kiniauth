<?php

namespace Kiniauth\Services\ImportExport;

use Kiniauth\Objects\Account\Account;
use Kiniauth\ValueObjects\ImportExport\ExportableProjectResources;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectExportConfig;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Logging\Logger;

class ImportExportService {

    /**
     * Import export service
     *
     * @param ProjectExporter $exporter
     * @param ProjectImporter $importer
     * @param ObjectBinder $objectBinder
     *
     */
    public function __construct(private ProjectExporter $exporter, private ProjectImporter $importer, private ObjectBinder $objectBinder) {
    }

    /**
     * Get exportable resources
     *
     * @param string $projectKey
     * @param int $accountId
     *
     * @return ExportableProjectResources
     */
    public function getExportableProjectResources(string $projectKey, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return $this->exporter->getExportableProjectResources($accountId, $projectKey);
    }

    /**
     * Export a project for an account and project key
     *
     * @param string $projectKey
     * @param mixed $exportProjectConfig
     * @param int $accountId
     *
     * @return ProjectExport
     */
    public function exportProject(string $projectKey, mixed $exportProjectConfig, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $mappedConfig = $this->objectBinder->bindFromArray($exportProjectConfig, $this->exporter::EXPORT_CONFIG_CLASS);
        return $this->exporter->exportProject($accountId, $projectKey, $mappedConfig);
    }


    /**
     * Analyse import
     *
     * @param string $projectKey
     * @param mixed $projectExport
     * @param $accountId
     * @return mixed
     */
    public function analyseImport(string $projectKey, mixed $projectExport, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $mappedExport = $this->objectBinder->bindFromArray($projectExport, $this->exporter::EXPORT_CLASS);
        return $this->importer->analyseImport($accountId, $projectKey, $mappedExport);
    }

    /**
     * Import a project from a project export into the supplied account and project
     *
     * @param int $accountId
     * @param string $projectKey
     * @param mixed $projectExport
     *
     */
    public function importProject(string $projectKey, mixed $projectExport, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $mappedExport = $this->objectBinder->bindFromArray($projectExport, $this->exporter::EXPORT_CLASS);
        $this->importer->importProject($accountId, $projectKey, $mappedExport);
    }

}