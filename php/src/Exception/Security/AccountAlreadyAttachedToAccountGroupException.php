<?php

namespace Kiniauth\Exception\Security;

use Kiniauth\Objects\Account\Account;

class AccountAlreadyAttachedToAccountGroupException extends \Exception {

    public function __construct($accountId) {
        /** @var Account $accountId */
        $account = Account::fetch($accountId);
        $accountName = $account->getName();
        parent::__construct("The account $accountName is already attached to this account group");
    }

}