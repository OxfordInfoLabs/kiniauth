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
     * @param ProjectImporterExporter $projectImporterExporter
     * @param ObjectBinder $objectBinder
     *
     */
    public function __construct(private ProjectImporterExporter $projectImporterExporter, private ObjectBinder $objectBinder) {
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
        return $this->projectImporterExporter->getExportableProjectResources($accountId, $projectKey);
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
        return $this->projectImporterExporter->exportProject($accountId, $projectKey, $exportProjectConfig);
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
        return $this->projectImporterExporter->analyseImport($accountId, $projectKey, $projectExport);
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
        $this->projectImporterExporter->importProject($accountId, $projectKey, $projectExport);
    }

}