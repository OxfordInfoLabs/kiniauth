<?php

namespace Kiniauth\ValueObjects\ImportExport;

/**
 * Placeholder value object
 */
class ProjectExportConfig {

    /**
     * @param mixed] $includedNotificationGroupIds
     */
    public function __construct(private array $includedNotificationGroupIds = []) {
    }

    /**
     * @return int[]
     */
    public function getIncludedNotificationGroupIds(): array {
        return $this->includedNotificationGroupIds;
    }


}