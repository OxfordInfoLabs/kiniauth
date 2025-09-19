<?php

namespace Kiniauth\Objects\Account;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Account Group object used for creating groups of accounts
 *
 * @table ka_account_group
 * @generate
 */
class AccountGroup extends ActiveRecord {

    /**
     * @var int
     * @primaryKey
     * @autoIncrement
     */
    private $id;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var int
     * @required
     */
    private int $ownerAccountId;

    /**
     * @param int $id
     * @param string $name
     * @param int $ownerAccountId
     */
    public function __construct(string $name = null, int $ownerAccountId = null) {
        $this->name = $name;
        $this->ownerAccountId = $ownerAccountId;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
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

}