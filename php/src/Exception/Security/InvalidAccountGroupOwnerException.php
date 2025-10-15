<?php

namespace Kiniauth\Exception\Security;

class InvalidAccountGroupOwnerException extends \Exception {

    public function __construct($message = null) {
        parent::__construct($message ?: "The logged in account doesn't own the account group");
    }

}