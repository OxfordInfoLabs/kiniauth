<?php

namespace Kiniauth\Services\ImportExport;

use Kiniauth\Services\ImportExport\ImportExporters\NotificationGroupImportExporter;
use Kiniauth\ValueObjects\ImportExport\ExportableProjectResources;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectImportAnalysis;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Logging\Logger;

class ProjectImporterExporter {

    /**
     * @var ImportExporter[]
     */
    private $importExporters = [];

    /**
     * Construct with notification group import exporter
     *
     * @param NotificationGroupImportExporter $notificationGroupImportExporter
     */
    public function __construct(NotificationGroupImportExporter $notificationGroupImportExporter, private ObjectBinder $objectBinder) {
        $this->importExporters[] = $notificationGroupImportExporter;

    }


    /**
     * Inject additional import exporters
     *
     * @param ImportExporter $importExporter
     * @return void
     */
    public function addImportExporter($importExporter) {
        $this->importExporters[] = $importExporter;
    }


    /**
     * Get exportable project resources
     *
     * @param int $accountId
     * @param string $projectKey
     * @return ExportableProjectResources
     */
    public function getExportableProjectResources(int $accountId, string $projectKey) {

        // Loop through all installed import exporters and generate project resources
        $projectResources = [];
        foreach ($this->importExporters as $importExporter) {
            $projectResources[$importExporter->getObjectTypeCollectionIdentifier()] = $importExporter->getExportableProjectResources($accountId, $projectKey);
        }
        return new ExportableProjectResources($projectResources);

    }

    /**
     * Export a project
     *
     * @param int $accountId
     * @param string $projectKey
     * @param mixed $exportProjectConfig
     * @return ProjectExport
     */
    public function exportProject(int $accountId, string $projectKey, mixed $exportProjectConfig) {

        $mappedProjectConfig = $this->mapExportConfigObjects($exportProjectConfig);

        // Loop through all installed import exporters and generate project resources
        $projectResources = [];
        $remappedProjectConfig = [];
        foreach ($this->importExporters as $importExporter) {

            $importExporterIdentifier = $importExporter->getObjectTypeCollectionIdentifier();

            $projectResources[$importExporter->getObjectTypeCollectionIdentifier()] = $importExporter->createExportObjects($accountId, $projectKey,
                $mappedProjectConfig[$importExporterIdentifier], $mappedProjectConfig);

            // Remap project config with new keys
            $remappedProjectConfig[$importExporterIdentifier] = [];
            foreach ($mappedProjectConfig[$importExporterIdentifier] as $identifier => $config) {
                $remappedProjectConfig[$importExporterIdentifier][ImportExporter::remapExportObjectPK($importExporterIdentifier, $identifier)] = $config;
            }

        }

        return new ProjectExport($projectResources, $remappedProjectConfig);

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

        $mappedProjectConfig = $this->mapExportConfigObjects($projectExport->getExportProjectConfig());


        // Loop through all installed import exporters and generate project resources
        $importResources = [];
        foreach ($this->importExporters as $importExporter) {

            $exportObjects = array_map(function ($exportItem) use ($importExporter) {
                return $this->objectBinder->bindFromArray($exportItem, $importExporter->getObjectTypeImportClassName());
            },
                $projectExport->getExportData()[$importExporter->getObjectTypeCollectionIdentifier()] ?? []);


            $resources = $importExporter->analyseImportObjects($accountId, $projectKey, $exportObjects,
                $mappedProjectConfig[$importExporter->getObjectTypeCollectionIdentifier()]);

            foreach ($resources as $resource) {
                $category = $resource->getGroupingTitle() ?? $importExporter->getObjectTypeCollectionTitle();
                if (!isset($importResources[$category])) {
                    $importResources[$category] = [];
                }
                $importResources[$category][] = $resource;
            }

        }


        return new ProjectImportAnalysis($projectExport->getExportDateTime(), $importResources);


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

        $mappedProjectConfig = $this->mapExportConfigObjects($projectExport->getExportProjectConfig());


        // Loop through all installed import exporters and generate project resources
        foreach ($this->importExporters as $importExporter) {

            $exportObjects = array_map(function ($exportItem) use ($importExporter) {
                return $this->objectBinder->bindFromArray($exportItem, $importExporter->getObjectTypeImportClassName());
            },
                $projectExport->getExportData()[$importExporter->getObjectTypeCollectionIdentifier()] ?? []);


            $importExporter->importObjects($accountId, $projectKey, $exportObjects, $mappedProjectConfig[$importExporter->getObjectTypeCollectionIdentifier()]);
        }


    }

    /**
     * @param ImportExporter $importExporter
     * @param mixed $exportProjectConfig
     * @return mixed
     */
    private function mapExportConfigObjects($exportProjectConfig) {
        $mappedConfig = [];
        foreach ($this->importExporters as $importExporter) {
            $mappedConfigClass = $importExporter->getObjectTypeExportConfigClassName();
            $typeIdentifier = $importExporter->getObjectTypeCollectionIdentifier();
            $mappedConfig[$typeIdentifier] = [];
            foreach ($exportProjectConfig[$importExporter->getObjectTypeCollectionIdentifier()] as $identifier => $rawConfig) {
                $mappedConfig[$typeIdentifier][$identifier] = $this->objectBinder->bindFromArray($rawConfig, $mappedConfigClass);
            }
        }

        return $mappedConfig;
    }


}