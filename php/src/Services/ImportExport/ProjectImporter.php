<?php

namespace Kiniauth\Services\ImportExport;

use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectImportAnalysis;


/**
 * @implementation default Kiniauth\Services\ImportExport\DefaultProjectImporter
 * @defaultImplementation Kiniauth\Services\ImportExport\DefaultProjectImporter
 */
interface ProjectImporter {


    /**
     * Analyse an import for the specified account and project
     *
     * @param int $accountId
     * @param string $projectKey
     * @param ProjectExport $projectExport
     *
     * @return ProjectImportAnalysis
     */
    public function analyseImport(int $accountId, string $projectKey, ProjectExport $projectExport);

    /**
     * Import a project from a project export into the supplied account and project
     *
     * @param int $accountId
     * @param string $projectKey
     * @param ProjectExport $projectExport
     *
     */
    public function importProject(int $accountId, string $projectKey, ProjectExport $projectExport);

}