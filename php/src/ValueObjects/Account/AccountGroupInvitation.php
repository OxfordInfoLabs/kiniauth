<?php

namespace Kiniauth\ValueObjects\Account;

class AccountGroupInvitation {

    private int $accountGroupId;

    private string $accountGroupName;

    private int $accountId;

    private string $accountName;

    private ?string $expiryDate;

    /**
     * @param int $accountGroupId
     * @param string $accountGroupName
     * @param int $accountId
     * @param string $accountName
     * @param string $expiryDate
     */
    public function __construct(int $accountGroupId, string $accountGroupName, int $accountId, string $accountName, ?string $expiryDate = null) {
        $this->accountGroupId = $accountGroupId;
        $this->accountGroupName = $accountGroupName;
        $this->accountId = $accountId;
        $this->accountName = $accountName;
        $this->expiryDate = $expiryDate;
    }

    public function getAccountGroupId(): int {
        return $this->accountGroupId;
    }

    public function setAccountGroupId(int $accountGroupId): void {
        $this->accountGroupId = $accountGroupId;
    }

    public function getAccountGroupName(): string {
        return $this->accountGroupName;
    }

    public function setAccountGroupName(string $accountGroupName): void {
        $this->accountGroupName = $accountGroupName;
    }

    public function getAccountId(): int {
        return $this->accountId;
    }

    public function setAccountId(int $accountId): void {
        $this->accountId = $accountId;
    }

    public function getAccountName(): string {
        return $this->accountName;
    }

    public function setAccountName(string $accountName): void {
        $this->accountName = $accountName;
    }

    public function getExpiryDate(): ?string {
        return $this->expiryDate;
    }

    public function setExpiryDate(?string $expiryDate): void {
        $this->expiryDate = $expiryDate;
    }

}