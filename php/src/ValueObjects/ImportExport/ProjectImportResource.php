<?php

namespace Kiniauth\ValueObjects\ImportExport;


class ProjectImportResource {


    public function __construct(private mixed                       $identifier, private string $title,
                                private ProjectImportResourceStatus $importStatus,
                                private mixed                       $existingProjectIdentifier = null,
                                private ?string                     $groupingTitle = null,
                                private ?string                     $message = null) {

    }

    /**
     * @return mixed
     */
    public function getIdentifier(): mixed {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * @return ProjectImportResourceStatus
     */
    public function getImportStatus(): ProjectImportResourceStatus {
        return $this->importStatus;
    }

    /**
     * @return mixed
     */
    public function getExistingProjectIdentifier(): mixed {
        return $this->existingProjectIdentifier;
    }

    /**
     * @return string
     */
    public function getGroupingTitle(): ?string {
        return $this->groupingTitle;
    }

    /**
     * @return string
     */
    public function getMessage(): ?string {
        return $this->message;
    }


}