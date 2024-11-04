<?php

namespace Kiniauth\ValueObjects\ImportExport;

class ExportableProjectResources {

    /**
     * @param ProjectExportResource[][string] $resourcesByType
     */
    public function __construct(private array $resourcesByType) {
    }

    /**
     * @return  ProjectExportResource[][string]
     */
    public function getResourcesByType(): array {
        return $this->resourcesByType;
    }


    /**
     * Add an array of resources for a given type
     *
     * @param string $type
     * @param ProjectExportResource[] $resources
     */
    public function addResourcesForType(string $type, array $resources) {
        $this->resourcesByType[$type] = $resources;
    }
}