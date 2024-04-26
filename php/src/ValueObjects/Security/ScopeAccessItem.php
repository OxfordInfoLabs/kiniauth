<?php

namespace Kiniauth\ValueObjects\Security;

class ScopeAccessItem {

    /**
     * Construct a scope access item with a label and identifier
     *
     * @param string $scope
     * @param string $itemIdentifier
     * @param string $itemLabel
     * @param string $scopeLabel
     */
    public function __construct(
        private string  $scope,
        private string  $itemIdentifier,
        private ?string $itemLabel = null,
        private ?string $scopeLabel = null) {
    }

    /**
     * @return string
     */
    public function getScope(): string {
        return $this->scope;
    }


    /**
     * @return string
     */
    public function getItemIdentifier(): string {
        return $this->itemIdentifier;
    }

    /**
     * @return ?string
     */
    public function getItemLabel(): ?string {
        return $this->itemLabel;
    }

    /**
     * @return string|null
     */
    public function getScopeLabel(): ?string {
        return $this->scopeLabel;
    }

    /**
     * @param string|null $itemLabel
     */
    public function setItemLabel(?string $itemLabel): void {
        $this->itemLabel = $itemLabel;
    }

    /**
     * @param string|null $scopeLabel
     */
    public function setScopeLabel(?string $scopeLabel): void {
        $this->scopeLabel = $scopeLabel;
    }


}