<?php


namespace Kiniauth\Exception\Security;

class TooManyUserAccessTokensException extends \Exception {

    public function __construct() {
        parent::__construct("You have reached the maximum number of allowed access tokens");
    }

}
