<?php

namespace Kiniauth\Exception\Security;

class AccountGroupOwnerCannotLeaveException extends \Exception {

    public function __construct() {
        parent::__construct("The account group owner cannot leave their own group");
    }

}