<?php

namespace Kiniauth\ValueObjects\Account;

class AccountGroupInvitation {

    private int $accountGroupId;

    private int $accountId;

    private ?string $expiryDate;

    /**
     * @param int $accountGroupId
     * @param int $accountId
     * @param string $expiryDate
     */
    public function __construct(int $accountGroupId, int $accountId, ?string $expiryDate = null) {
        $this->accountGroupId = $accountGroupId;
        $this->accountId = $accountId;
        $this->expiryDate = $expiryDate;
    }

    public function getAccountGroupId(): int {
        return $this->accountGroupId;
    }

    public function setAccountGroupId(int $accountGroupId): void {
        $this->accountGroupId = $accountGroupId;
    }

    public function getAccountId(): int {
        return $this->accountId;
    }

    public function setAccountId(int $accountId): void {
        $this->accountId = $accountId;
    }

    public function getExpiryDate(): ?string {
        return $this->expiryDate;
    }

    public function setExpiryDate(?string $expiryDate): void {
        $this->expiryDate = $expiryDate;
    }

}