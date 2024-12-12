<?php

namespace Kiniauth\ValueObjects\ImportExport\ExportConfig;

class ObjectInclusionExportConfig {

    public function __construct(private bool $included) {
    }

    /**
     * @return bool
     */
    public function isIncluded(): bool {
        return $this->included;
    }


}