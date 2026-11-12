<?php

namespace Kiniauth\Exception\Security;

class AccountGroupOwnerCannotRemoveThemselvesException extends \Exception {

    public function __construct() {
        parent::__construct("The account group owner cannot remove themselves from their own group");
    }

}