<?php

namespace Kiniauth\Objects\Account;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_account
 */
class AccountLabel extends ActiveRecord {

    /**
     * @var int
     * @primaryKey
     * @column account_id
     */
    private ?int $relatedAccountId;

    /**
     * @var string
     */
    private ?string $name;

    /**
     * @param int $relatedAccountId
     * @param string $name
     */
    public function __construct(?int $relatedAccountId = null, ?string $name = null) {
        $this->relatedAccountId = $relatedAccountId;
        $this->name = $name;
    }


    /**
     * Get name of account
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }


}