<?php

namespace Kiniauth\Objects\ImportExport\ManagedProject;

use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_managed_project_version
 * @generate
 */
class ManagedProjectVersion extends ActiveRecord {

    /**
     * @var int
     */
    private ?int $id = null;

    /**
     * @var int
     */
    private ?int $managedProjectId;

    /**
     * @var ProjectExport
     * @sqlType LONGTEXT
     * @json
     */
    private ?ProjectExport $projectExport;

    /**
     * @var \DateTime
     */
    private ?\DateTime $exportDate;

    /**
     * @param int $managedProjectId
     * @param ProjectExport $projectExport
     * @param \DateTime $exportDate
     */
    public function __construct($managedProjectId = null, $projectExport = null, $exportDate = null) {
        $this->managedProjectId = $managedProjectId;
        $this->projectExport = $projectExport;
        $this->exportDate = $exportDate;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getManagedProjectId() {
        return $this->managedProjectId;
    }

    /**
     * @param int $managedProjectId
     * @return void
     */
    public function setManagedProjectId(int $managedProjectId): void {
        $this->managedProjectId = $managedProjectId;
    }

    /**
     * @return ProjectExport
     */
    public function getProjectExport() {
        return $this->projectExport;
    }

    /**
     * @param ProjectExport $projectExport
     * @return void
     */
    public function setProjectExport(ProjectExport $projectExport): void {
        $this->projectExport = $projectExport;
    }

    /**
     * @return \DateTime
     */
    public function getExportDate() {
        return $this->exportDate;
    }

    /**
     * @param \DateTime $exportDate
     * @return void
     */
    public function setExportDate($exportDate): void {
        $this->exportDate = $exportDate;
    }

}