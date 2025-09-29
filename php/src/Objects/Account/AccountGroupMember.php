<?php

namespace Kiniauth\Objects\Account;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Object for mapping accounts into account groups
 *
 * @table ka_account_group_member
 * @generate
 */
class AccountGroupMember extends ActiveRecord {

    /**
     * @var int
     * @primaryKey
     */
    private ?int $accountGroupId = null;

    /**
     * @var int
     * @primaryKey
     */
    private ?int $memberAccountId;

    /**
     * @param int $accountGroupId
     * @param int $memberAccountId
     */
    public function __construct(?int $accountGroupId = null, ?int $memberAccountId = null) {
        $this->accountGroupId = $accountGroupId;
        $this->memberAccountId = $memberAccountId;
    }

    /**
     * @return int
     */
    public function getAccountGroupId(): ?int {
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
    public function getMemberAccountId(): int {
        return $this->memberAccountId;
    }

    /**
     * @param int $memberAccountId
     * @return void
     */
    public function setMemberAccountId(int $memberAccountId): void {
        $this->memberAccountId = $memberAccountId;
    }

}