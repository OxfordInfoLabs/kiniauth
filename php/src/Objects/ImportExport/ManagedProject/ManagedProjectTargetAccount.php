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
    private $managedProjectId;


    /**
     * @var int
     * @primaryKey
     */
    private $targetAccountId;

    /**
     * @return int
     */
    public function getManagedProjectId(): int {
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
    public function getTargetAccountId(): int {
        return $this->targetAccountId;
    }

    /**
     * @param int $targetAccountId
     */
    public function setTargetAccountId(int $targetAccountId): void {
        $this->targetAccountId = $targetAccountId;
    }




}