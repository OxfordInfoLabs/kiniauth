<?php

namespace Kiniauth\Objects\Account;

/**
 * Object for mapping accounts into account groups
 *
 * @table ka_account_group_member
 * @generate
 */
class AccountGroupMember {

    /**
     * @var int
     * @primaryKey
     */
    private int $accountGroupId;

    /**
     * @var int
     * @primaryKey
     */
    private int $accountId;

    /**
     * @param int $accountGroupId
     * @param int $accountId
     */
    public function __construct(int $accountGroupId = null, int $accountId = null) {
        $this->accountGroupId = $accountGroupId;
        $this->accountId = $accountId;
    }

    /**
     * @return int
     */
    public function getAccountGroupId(): int {
        return $this->accountGroupId;
    }

    /**
     * @param int $accountGroupId
     * @return void
     */
    public function setAccountGroupId(int $accountGroupId): void {
        $this->accountGroupId = $accountGroupId;
    }

    /**
     * @return int
     */
    public function getAccountId(): int {
        return $this->accountId;
    }

    /**
     * @param int $accountId
     * @return void
     */
    public function setAccountId(int $accountId): void {
        $this->accountId = $accountId;
    }

}