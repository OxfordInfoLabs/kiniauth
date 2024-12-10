<?php

namespace Kiniauth\ValueObjects\ImportExport\ExportObjects;

class ExportedApiKey {

    /**
     * Construct with required items
     *
     * @param int $id
     * @param string $description
     * @param array $projectRoleIds
     */
    public function __construct(private int $id, private string $description, private array $projectRoleIds) {
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void {
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @return array
     */
    public function getProjectRoleIds(): array {
        return $this->projectRoleIds;
    }


}