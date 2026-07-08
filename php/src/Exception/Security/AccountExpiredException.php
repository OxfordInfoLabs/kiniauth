<?php


namespace Kiniauth\Exception\Security;


class AccountExpiredException extends \Exception {

    public function __construct() {
        parent::__construct("Your account has expired.");
    }

}
