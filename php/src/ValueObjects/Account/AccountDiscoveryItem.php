<?php

namespace Kiniauth\ValueObjects\Account;

class AccountDiscoveryItem {

    /**
     * Discovery settings for an account
     *
     * @param string $accountName
     * @param bool $discoverable
     * @param string|null $externalIdentifier
     */
    public function __construct(
        private string  $accountName,
        private ?bool   $discoverable = false,
        private ?string $externalIdentifier = null) {
    }

    /**
     * @return string
     */
    public function getAccountName(): string {
        return $this->accountName;
    }

    /**
     * @return bool|null
     */
    public function getDiscoverable(): ?bool {
        return $this->discoverable;
    }

    /**
     * @return string|null
     */
    public function getExternalIdentifier(): ?string {
        return $this->externalIdentifier;
    }


}