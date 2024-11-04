<?php

namespace Kiniauth\ValueObjects\ImportExport;

enum ProjectImportResourceStatus {
    case Create;
    case Update;
    case Ignore;
    case Delete;
}

class ProjectImportResource {


    public function __construct(private mixed                       $identifier, private string $title,
                                private ProjectImportResourceStatus $importStatus) {

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


}