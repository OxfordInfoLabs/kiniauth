<?php


namespace Kiniauth\Exception\Security;


class InvalidAccountForUserException extends \Exception {

    public function __construct() {
        parent::__construct("The user does not have access to the supplied account");
    }

}
