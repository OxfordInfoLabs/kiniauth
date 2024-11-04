<?php

namespace Kiniauth\ValueObjects\ImportExport;

class ProjectExportResource {

    public function __construct(private mixed $identifier, private string $title) {
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


}