<?php


namespace Kiniauth\Exception\Security;


class InvalidLoginException extends \Exception {

    public function __construct() {
        parent::__construct("The username or password supplied was invalid");
    }

}
