<?php

namespace Kiniauth\Services\ImportExport;

use Kiniauth\ValueObjects\ImportExport\ExportableProjectResources;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectExportConfig;

/**
 * @implementation default Kiniauth\Services\ImportExport\DefaultProjectExporter
 * @defaultImplementation Kiniauth\Services\ImportExport\DefaultProjectExporter
 */
interface ProjectExporter {

    // Defined the export class for exporting
    public const EXPORT_CLASS = ProjectExport::class;
    public const EXPORT_CONFIG_CLASS = ProjectExportConfig::class;

    /**
     * Get exportable resources
     *
     * @param int $accountId
     * @param string $projectKey
     *
     * @return ExportableProjectResources
     */
    public function getExportableProjectResources(int $accountId, string $projectKey);


    /**
     * Export a project for an account and project key
     *
     * @param int $accountId
     * @param string $projectKey
     * @param ProjectExportConfig $exportProjectConfig
     * @return ProjectExport
     */
    public function exportProject(int $accountId, string $projectKey, ProjectExportConfig $exportProjectConfig);


}