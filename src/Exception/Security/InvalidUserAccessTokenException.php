<?php


namespace Kiniauth\Exception\Security;


class InvalidUserAccessTokenException extends \Exception {

    public function __construct() {
        parent::__construct("The user access token supplied was invalid");
    }

}
