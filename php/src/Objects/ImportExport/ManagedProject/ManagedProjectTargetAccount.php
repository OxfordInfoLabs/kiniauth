<?php

namespace Kiniauth\Objects\ImportExport\ManagedProject;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_managed_project_target_account
 * @generate
 */
class ManagedProjectTargetAccount extends ActiveRecord {

    /**
     * @var int
     * @primaryKey
     */
    private ?int $managedProjectId;


    /**
     * @var int
     * @primaryKey
     */
    private ?int $targetAccountId;

    /**
     * @param int $managedProjectId
     * @param int $targetAccountId
     */
    public function __construct($managedProjectId = null, $targetAccountId = null) {
        $this->managedProjectId = $managedProjectId;
        $this->targetAccountId = $targetAccountId;
    }

    /**
     * @return int
     */
    public function getManagedProjectId() {
        return $this->managedProjectId;
    }

    /**
     * @param int $managedProjectId
     */
    public function setManagedProjectId(int $managedProjectId): void {
        $this->managedProjectId = $managedProjectId;
    }

    /**
     * @return int
     */
    public function getTargetAccountId() {
        return $this->targetAccountId;
    }

    /**
     * @param int $targetAccountId
     */
    public function setTargetAccountId(int $targetAccountId): void {
        $this->targetAccountId = $targetAccountId;
    }

}