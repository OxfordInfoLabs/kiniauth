<?php

namespace Kiniauth\ValueObjects\Account;

class AccountInvitation {

    private int $accountId;

    private string $emailAddress;

    private string $expiryDate;

    /**
     * @param int $accountId
     * @param string $emailAddress
     * @param string $expiryDate
     */
    public function __construct(int $accountId, string $emailAddress, string $expiryDate) {
        $this->accountId = $accountId;
        $this->emailAddress = $emailAddress;
        $this->expiryDate = $expiryDate;
    }

    public function getAccountId(): int {
        return $this->accountId;
    }

    public function setAccountId(int $accountId): void {
        $this->accountId = $accountId;
    }

    public function getEmailAddress(): string {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): void {
        $this->emailAddress = $emailAddress;
    }

    public function getExpiryDate(): string {
        return $this->expiryDate;
    }

    public function setExpiryDate(string $expiryDate): void {
        $this->expiryDate = $expiryDate;
    }

}