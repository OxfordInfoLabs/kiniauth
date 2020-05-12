<?php


namespace Kiniauth\Exception\Security;


class InvalidLoginException extends \Exception {

    public function __construct($message = null) {
        parent::__construct($message ? $message : "The username or password supplied was invalid");
    }

}
