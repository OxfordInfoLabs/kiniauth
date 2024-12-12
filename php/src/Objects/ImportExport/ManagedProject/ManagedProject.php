<?php

namespace Kiniauth\Objects\ImportExport\ManagedProject;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_managed_project
 * @generate
 */
class ManagedProject extends ActiveRecord {

    /**
     * @var int
     */
    private int $id;

    /**
     * @var string
     */
    private string $name;


    /**
     * @var int
     */
    private int $sourceAccountId;

    /**
     * @var string
     */
    private string $sourceProjectKey;

    /**
     * @var mixed
     */
    private mixed $exportConfig;

    /**
     * @var string
     */
    private string $exportConfigClass;


    /**
     * @var ManagedProjectTargetAccount[]
     * @oneToMany
     * @childJoinColumns managed_project_id
     */
    private array $targetAccounts;


    /**
     * Construct a new managed project
     *
     * @param string $name
     * @param int $sourceAccountId
     * @param int $sourceProjectKey
     */
    public function __construct(string $name, int $sourceAccountId, string $sourceProjectKey) {
        $this->name = $name;
        $this->sourceAccountId = $sourceAccountId;
        $this->sourceProjectKey = $sourceProjectKey;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string {
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
    public function getSourceAccountId(): int {
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
    public function getSourceProjectKey(): string {
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
    public function getExportConfig(): mixed {
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
    public function getExportConfigClass(): string {
        return $this->exportConfigClass;
    }

    /**
     * @param string $exportConfigClass
     */
    public function setExportConfigClass(string $exportConfigClass): void {
        $this->exportConfigClass = $exportConfigClass;
    }


}