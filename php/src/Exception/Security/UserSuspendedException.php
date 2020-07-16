<?php


namespace Kiniauth\Exception\Security;


class UserSuspendedException extends \Exception {

    public function __construct() {
        parent::__construct("Your user account has been suspended.");
    }

}
