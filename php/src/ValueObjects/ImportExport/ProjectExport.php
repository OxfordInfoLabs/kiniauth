<?php

namespace Kiniauth\ValueObjects\ImportExport;

use Kiniauth\Objects\Communication\Notification\NotificationGroup;

/**
 * An export of a project
 */
class ProjectExport {

    /**
     * @var string
     */
    private $exportDateTime;

    /**
     * Notification groups
     *
     * @param ProjectExportConfig $exportConfig
     * @param NotificationGroup[] $notificationGroups
     */
    public function __construct(private ProjectExportConfig $exportConfig, private array $notificationGroups) {
        if (!$this->exportDateTime)
            $this->exportDateTime = date("Y-m-d H:i:s");
    }

    /**
     * @return string
     */
    public function getExportDateTime(): string {
        return $this->exportDateTime;
    }


    /**
     * @return ProjectExportConfig
     */
    public function getExportConfig(): ProjectExportConfig {
        return $this->exportConfig;
    }

    /**
     * @return NotificationGroup[]
     */
    public function getNotificationGroups(): array {
        return $this->notificationGroups;
    }


}