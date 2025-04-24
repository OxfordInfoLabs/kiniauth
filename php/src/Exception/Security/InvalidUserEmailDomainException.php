<?php

namespace Kiniauth\Exception\Security;

class InvalidUserEmailDomainException extends \Exception {

    public function __construct($emailAddress) {
        parent::__construct("The user with email address $emailAddress cannot be added to this account as it doesn't match one of the account defined suffixes");
    }

}