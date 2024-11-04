<?php

namespace Kiniauth\ValueObjects\ImportExport;

/**
 * Placeholder value object
 */
class ProjectExportConfig {

    /**
     * @param int[] $includedNotificationGroupIds
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