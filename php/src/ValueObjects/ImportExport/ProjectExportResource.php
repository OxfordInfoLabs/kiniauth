<?php

namespace Kiniauth\ValueObjects\ImportExport;

class ProjectExportResource {

    public function __construct(private mixed $identifier, private string $title, private mixed $defaultConfig,
                                private string $category = "") {
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
     * @return mixed
     */
    public function getDefaultConfig(): mixed {
        return $this->defaultConfig;
    }

    /**
     * @return string
     */
    public function getCategory(): string {
        return $this->category;
    }


}