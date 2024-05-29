<?php

namespace Kiniauth\ValueObjects\Security;

/**
 * Used for returning sharable items for display purposes etc.
 */
class SharableItem {

    public function __construct(private string $sharableTypeLabel,
                                private string $sharableTitle) {

    }

    /**
     * @return string
     */
    public function getSharableTypeLabel(): string {
        return $this->sharableTypeLabel;
    }

    /**
     * @return string
     */
    public function getSharableTitle(): string {
        return $this->sharableTitle;
    }


}