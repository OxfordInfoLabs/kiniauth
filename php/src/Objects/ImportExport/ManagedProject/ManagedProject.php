<?php

namespace Kiniauth\Objects\ImportExport\ManagedProject;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_managed_project
 * @generate
 */
class ManagedProject extends ManagedProjectSummary {

    /**
     * @var mixed
     */
    private mixed $exportConfig = null;

    /**
     * @var string
     */
    private ?string $exportConfigClass = null;

    /**
     * @var ManagedProjectTargetAccount[]
     * @oneToMany
     * @childJoinColumns managed_project_id
     */
    private ?array $targetAccounts = [];

    /**
     * @var ManagedProjectVersion[]
     * @oneToMany
     * @childJoinColumns managed_project_id
     */
    private ?array $versions = [];

    /**
     * Construct a new managed project
     *
     * @param string $name
     * @param int $sourceAccountId
     * @param string $sourceProjectKey
     */
    public function __construct($name = null, $sourceAccountId = null, $sourceProjectKey = null) {
        $this->name = $name;
        $this->sourceAccountId = $sourceAccountId;
        $this->sourceProjectKey = $sourceProjectKey;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getSourceAccountId() {
        return $this->sourceAccountId;
    }

    /**
     * @param int $sourceAccountId
     */
    public function setSourceAccountId(int $sourceAccountId): void {
        $this->sourceAccountId = $sourceAccountId;
    }

    /**
     * @return string
     */
    public function getSourceProjectKey() {
        return $this->sourceProjectKey;
    }

    /**
     * @param string $sourceProjectKey
     */
    public function setSourceProjectKey(string $sourceProjectKey): void {
        $this->sourceProjectKey = $sourceProjectKey;
    }

    /**
     * @return mixed
     */
    public function getExportConfig() {
        return $this->exportConfig;
    }

    /**
     * @param mixed $exportConfig
     */
    public function setExportConfig(mixed $exportConfig): void {
        $this->exportConfig = $exportConfig;
    }

    /**
     * @return string
     */
    public function getExportConfigClass() {
        return $this->exportConfigClass;
    }

    /**
     * @param string $exportConfigClass
     */
    public function setExportConfigClass(string $exportConfigClass): void {
        $this->exportConfigClass = $exportConfigClass;
    }

    /**
     * @return ManagedProjectTargetAccount[]
     */
    public function getTargetAccounts() {
        return $this->targetAccounts;
    }

    /**
     * @param ManagedProjectTargetAccount[] $targetAccounts
     * @return void
     */
    public function setTargetAccounts(array $targetAccounts): void {
        $this->targetAccounts = $targetAccounts;
    }

    /**
     * @return ManagedProjectVersion[]
     */
    public function getVersions() {
        return $this->versions;
    }

}