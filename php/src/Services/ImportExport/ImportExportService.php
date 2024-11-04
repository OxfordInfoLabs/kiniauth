<?php

namespace Kiniauth\Services\ImportExport;

use Kiniauth\Objects\Account\Account;
use Kiniauth\ValueObjects\ImportExport\ExportableProjectResources;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectExportConfig;

class ImportExportService {

    /**
     * Import export service
     *
     * @param ProjectExporter $exporter
     * @param ProjectImporter $importer
     */
    public function __construct(private ProjectExporter $exporter, private ProjectImporter $importer) {
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
     * @param ProjectExportConfig $exportProjectConfig
     * @param int $accountId
     *
     * @return ProjectExport
     */
    public function exportProject(string $projectKey, ProjectExportConfig $exportProjectConfig, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return $this->exporter->exportProject($accountId, $projectKey, $exportProjectConfig);
    }


    /**
     * Analyse import
     *
     * @param string $projectKey
     * @param ProjectExport $projectExport
     * @param $accountId
     * @return mixed
     */
    public function analyseImport(string $projectKey, ProjectExport $projectExport, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return $this->importer->analyseImport($accountId, $projectKey, $projectExport);
    }

    /**
     * Import a project from a project export into the supplied account and project
     *
     * @param int $accountId
     * @param string $projectKey
     * @param ProjectExport $projectExport
     *
     */
    public function importProject(string $projectKey, ProjectExport $projectExport, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $this->importer->importProject($accountId, $projectKey, $projectExport);
    }

}