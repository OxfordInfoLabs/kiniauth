<?php

namespace Kiniauth\ValueObjects\ImportExport;

class ProjectImportAnalysis {

    /**
     * @param string $exportDateTime
     * @param ProjectImportResource[][string] $resourcesByType
     */
    public function __construct(private string $exportDateTime, private array $resourcesByType) {
    }

    /**
     * @return string
     */
    public function getExportDateTime(): string {
        return $this->exportDateTime;
    }


    /**
     * @return  ProjectImportResource[][string]
     */
    public function getResourcesByType(): array {
        return $this->resourcesByType;
    }


    /**
     * Add an array of resources for a given type
     *
     * @param string $type
     * @param ProjectImportResource[] $resources
     */
    public function addResourcesForType(string $type, array $resources) {
        $this->resourcesByType[$type] = $resources;
    }

}