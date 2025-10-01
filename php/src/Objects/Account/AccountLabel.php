<?php

namespace Kiniauth\Objects\Account;

/**
 * @table ka_account
 */
class AccountLabel {

    /**
     * @var int
     * @primaryKey
     * @column account_id
     */
    private int $relatedAccountId;

    /**
     * @var string
     */
    private string $name;

    /**
     * Get name of account
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }


}