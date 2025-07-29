<?php

namespace Kiniauth\Objects\ImportExport\ManagedProject;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_managed_project
 */
class ManagedProjectSummary extends ActiveRecord {

    /**
     * @var int
     */
    protected ?int $id = null;

    /**
     * @var string
     */
    protected ?string $name = null;

    /**
     * @var int
     */
    protected ?int $sourceAccountId = null;

    /**
     * @var string
     */
    protected ?string $sourceProjectKey = null;

    /**
     * @param int $id
     * @param string $name
     */
    public function __construct(?int $id, ?string $name, ?int $sourceAccountId, ?string $sourceProjectKey) {
        $this->id = $id;
        $this->name = $name;
        $this->sourceAccountId = $sourceAccountId;
        $this->sourceProjectKey = $sourceProjectKey;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getSourceAccountId() {
        return $this->sourceAccountId;
    }

    public function getSourceProjectKey() {
        return $this->sourceProjectKey;
    }

}