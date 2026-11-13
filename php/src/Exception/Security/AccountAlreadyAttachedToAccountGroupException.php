<?php

namespace Kiniauth\Exception\Security;

use Exception;
use Kiniauth\Objects\Account\Account;

class AccountAlreadyAttachedToAccountGroupException extends Exception {

    public function __construct(string $accountName) {
        parent::__construct("The account $accountName is already attached to this account group");
    }

}