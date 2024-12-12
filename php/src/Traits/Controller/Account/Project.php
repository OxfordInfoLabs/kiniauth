<?php


namespace Kiniauth\Traits\Controller\Account;


use Kiniauth\Objects\Account\ProjectSummary;
use Kiniauth\Services\Account\ProjectService;
use Kiniauth\Services\ImportExport\ImportExportService;
use Kiniauth\ValueObjects\Account\ProjectUpdateDescriptor;
use Kiniauth\ValueObjects\ImportExport\ExportableProjectResources;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectImportAnalysis;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use Kinikit\MVC\ContentSource\StringContentSource;
use Kinikit\MVC\Response\Download;
use Kinikit\MVC\Request\FileUpload;

trait Project {

    /**
     * @var ProjectService
     */
    private $projectService;

    /**
     * @var ImportExportService
     */
    private $importExportService;

    /**
     * @var ObjectToJSONConverter
     */
    private $objectToJSONConverter;


    /**
     * @var JSONToObjectConverter
     */
    private $jsonToObjectConverter;


    /**
     * Project constructor.
     * @param ProjectService $projectService
     * @param ImportExportService $importExportService
     * @param ObjectToJSONConverter $objectToJSONConverter
     * @param JSONToObjectConverter $jsonToObjectConverter
     */
    public function __construct($projectService, $importExportService, $objectToJSONConverter, $jsonToObjectConverter) {
        $this->projectService = $projectService;
        $this->importExportService = $importExportService;
        $this->objectToJSONConverter = $objectToJSONConverter;
        $this->jsonToObjectConverter = $jsonToObjectConverter;
    }


    /**
     * @http GET /$projectKey
     *
     * @param $projectKey
     * @return ProjectSummary
     */
    public function getProject($projectKey) {
        return $this->projectService->getProject($projectKey);
    }


    /**
     * @http GET /
     *
     * @param string $filterString
     * @param int $limit
     * @param int $offset
     *
     * @return ProjectSummary[]
     */
    public function filterProjects($filterString = "", $limit = 10, $offset = 0) {
        return $this->projectService->filterProjects($filterString, $offset, $limit);
    }

    /**
     * @http POST /
     *
     * @param ProjectUpdateDescriptor $projectUpdateDescriptor
     */
    public function createProject($projectUpdateDescriptor) {
        $projectSummary = new ProjectSummary($projectUpdateDescriptor->getName(), $projectUpdateDescriptor->getDescription());
        $this->projectService->saveProject($projectSummary);
    }


    /**
     * Update a project using supplied key and descriptor
     *
     * @http PUT /$projectKey
     *
     * @param $projectKey
     * @param ProjectUpdateDescriptor $projectUpdateDescriptor
     */
    public function updateProject($projectKey, $projectUpdateDescriptor) {
        $projectSummary = new ProjectSummary($projectUpdateDescriptor->getName(), $projectUpdateDescriptor->getDescription(), $projectKey);
        $this->projectService->saveProject($projectSummary);
    }


    /**
     * Remove a project by key
     *
     * @http DELETE /$projectKey
     *
     * @param $projectKey
     */
    public function removeProject($projectKey) {
        $this->projectService->removeProject($projectKey);
    }

    /**
     * Update the project settings object
     *
     * @http PUT /$projectKey/settings
     *
     * @param $projectKey
     * @param mixed $settings
     * @return void
     */
    public function updateProjectSettings($projectKey, $settings) {
        $project = $this->getProject($projectKey);
        $project->setSettings($settings);
        $this->projectService->saveProject($project);
    }


    /**
     * Get exportable project resources for a project key.
     *
     * @http GET /export/resources/$projectKey
     *
     * @param string $projectKey
     * @return ExportableProjectResources
     */
    public function getExportableProjectResources(string $projectKey) {
        return $this->importExportService->getExportableProjectResources($projectKey);
    }

    /**
     * Export a project using the passed key and config
     *
     * @http POST /export/$projectKey
     *
     * @param string $projectKey
     * @param mixed $projectExportConfig
     *
     * @return null
     */
    public function exportProject(string $projectKey, mixed $projectExportConfig) {

        $projectExport = $this->importExportService->exportProject($projectKey, $projectExportConfig);
        return new Download(new StringContentSource($this->objectToJSONConverter->convert($projectExport)),
            $projectKey . "-export-" . date("U") . ".json");
    }


    /**
     * Analyse an import for a project
     *
     * @http POST /import/analyse/$projectKey
     *
     * @param string $projectKey
     * @param FileUpload[] $importedFiles
     *
     * @return ProjectImportAnalysis
     */
    public function analyseProjectImport(string $projectKey, $importedFiles) {
        if (sizeof($importedFiles) > 0) {
            $projectExport = json_decode(file_get_contents(array_values($importedFiles)[0]->getTemporaryFilePath()),true);
            return $this->importExportService->analyseImport($projectKey, $projectExport);
        }
    }


    /**
     * Import a project
     *
     * @http POST /import/$projectKey
     *
     * @param string $projectKey
     * @param FileUpload[] $importedFiles
     */
    public function importProject(string $projectKey, $importedFiles) {
        if (sizeof($importedFiles) > 0) {
            $projectExport = json_decode(file_get_contents(array_values($importedFiles)[0]->getTemporaryFilePath()),true);
            $this->importExportService->importProject($projectKey, $projectExport);
        }
    }


}
