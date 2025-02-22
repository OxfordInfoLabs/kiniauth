<?php

namespace Kiniauth\Services\ImportExport;


use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;

abstract class ImportExporter {

    // Static mappings array
    private static $exportPKMappings = [];
    private static $importItemIdMap = [];


    /**
     * An identifier for the collection of objects being exported / imported
     * e.g. notificationGroups
     *
     * @return string
     */
    public abstract function getObjectTypeCollectionIdentifier();

    /**
     * A display title for the collection of objects being exported / imported
     * e.g. Notification Groups
     *
     * @return string
     */
    public abstract function getObjectTypeCollectionTitle();


    /**
     * Get the class name to use to map objects for import from raw export data.
     *
     * @return string
     */
    public abstract function getObjectTypeImportClassName();


    /**
     * Get the class name used to map the export config for items of this type
     *
     * @return mixed
     */
    public abstract function getObjectTypeExportConfigClassName();

    /**
     * Get exportable project resources for the type of object being exported.
     *
     * @param int $accountId
     * @param string $projectKey
     * @return ProjectExportResource[]
     */
    public abstract function getExportableProjectResources(int $accountId, string $projectKey);


    /**
     * Create export objects
     *
     * @param int $accountId
     * @param string $projectKey
     * @param mixed $objectExportConfig
     * @param mixed $allProjectExportConfig
     *
     * @return mixed[]
     */
    public abstract function createExportObjects(int $accountId, string $projectKey, mixed $objectExportConfig, mixed $allProjectExportConfig);


    /**
     * Analyse import objects for this type using the export objects
     *
     * @param int $accountId
     * @param string $projectKey
     * @param mixed[] $exportObjects
     * @param mixed $exportConfig
     *
     * @return ProjectImportResource[]
     */
    public abstract function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig);


    /**
     * Import project objects for this type using the supplied array of export objects and export project config
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $objectExportConfig
     *
     * @return void
     */
    public abstract function importObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig);


    /**
     * Get next item pk
     *
     * @param $itemType
     * @return int
     */
    public static function getNewExportPK($itemType, $existingPK) {
        if (!isset(self::$exportPKMappings[$itemType])) {
            self::$exportPKMappings[$itemType] = [];
        }

        // If a mapping has already been created return it to avoid duplication !
        if (isset(self::$exportPKMappings[$itemType]["PK".$existingPK]))
            return self::$exportPKMappings[$itemType]["PK".$existingPK];

        $nextItemPk = -sizeof(self::$exportPKMappings[$itemType]) - 1;
        self::$exportPKMappings[$itemType]["PK" . $existingPK] = $nextItemPk;

        return $nextItemPk;
    }


    /**
     * Remap an object pk if one has already been mapped, otherwise return intact
     *
     * @param $itemType
     * @param $existingPK
     * @return int
     */
    public static function remapExportObjectPK($itemType, $existingPK) {
        return self::$exportPKMappings[$itemType]["PK" . $existingPK] ?? $existingPK;
    }


    /**
     * Set a mapping from an imported item id to a new one
     *
     * @param string $itemType
     * @param mixed $importId
     * @param mixed $newId
     *
     * @return void
     */
    public static function setImportItemIdMapping($itemType, $importId, $newId) {
        if (!isset(self::$importItemIdMap[$itemType])) {
            self::$importItemIdMap[$itemType] = [];
        }
        self::$importItemIdMap[$itemType][$importId] = $newId;
    }


    /**
     * If a stored mapping for an item use it, otherwise use the passed value
     *
     * @param $itemType
     * @param $importId
     *
     * @return mixed
     */
    protected function remapImportedItemId($itemType, $importId) {
        return self::$importItemIdMap[$itemType][$importId] ?? $importId;
    }

    public static function resetData() {
        self::$importItemIdMap = [];
        self::$exportPKMappings = [];
    }


}