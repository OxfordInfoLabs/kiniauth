<?php

namespace Kiniauth\Objects\Account;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Account Group object used for creating groups of accounts
 *
 * @table ka_account_group
 * @generate
 * @interceptor \Kiniauth\Services\Security\AccountGroupInterceptor
 */
class AccountGroup extends ActiveRecord {

    /**
     * @var int
     * @primaryKey
     * @autoIncrement
     */
    private ?int $accountGroupId = null;

    /**
     * @var string
     */
    private ?string $name;

    /**
     * @var string
     */
    private ?string $description;

    /**
     * @var int
     * @required
     */
    private ?int $ownerAccountId;

    /**
     * @var AccountGroupMember[]
     * @oneToMany
     * @childJoinColumns account_group_id
     */
    private array $accountGroupMembers;

    /**
     * @param string $name
     * @param string $description
     * @param int $ownerAccountId
     * @param AccountGroupMember[] $accountGroupMembers
     */
    public function __construct(?string $name = null, ?string $description = null, ?int $ownerAccountId = null, ?array $accountGroupMembers = [],
                                ?int    $accountGroupId = null) {
        $this->name = $name;
        $this->description = $description;
        $this->ownerAccountId = $ownerAccountId;
        $this->accountGroupMembers = $accountGroupMembers;
        $this->accountGroupId = $accountGroupId;
    }

    /**
     * @return int
     */
    public function getId(): ?int {
        return $this->accountGroupId;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name): void {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription(string $description): void {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getOwnerAccountId(): int {
        return $this->ownerAccountId;
    }

    /**
     * @param int $ownerAccountId
     * @return void
     */
    public function setOwnerAccountId(int $ownerAccountId): void {
        $this->ownerAccountId = $ownerAccountId;
    }

    /**
     * @return AccountGroupMember[]
     */
    public function getAccountGroupMembers(): array {
        return $this->accountGroupMembers;
    }

    /**
     * @param AccountGroupMember[] $accountGroupMembers
     * @return void
     */
    public function setAccountGroupMembers(array $accountGroupMembers): void {
        $this->accountGroupMembers = $accountGroupMembers;
    }

    public function addMember(int $accountId): void {
        $newMember = new AccountGroupMember($this->accountGroupId, $accountId);
        $this->accountGroupMembers[] = $newMember;
    }

}