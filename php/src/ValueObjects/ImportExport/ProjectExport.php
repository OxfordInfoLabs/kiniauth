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
     * Export data and export project config
     *
     * @param mixed $exportData
     * @param mixed $exportProjectConfig
     */
    public function __construct(private mixed $exportData, private mixed $exportProjectConfig) {
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
     * @return mixed
     */
    public function getExportData(): mixed {
        return $this->exportData;
    }

    /**
     * @return mixed
     */
    public function getExportProjectConfig(): mixed {
        return $this->exportProjectConfig;
    }


}