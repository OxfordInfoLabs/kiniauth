<?php


namespace Kiniauth\Exception\Security;


use Kinikit\Core\Exception\SerialisableException;

class InvalidAPICredentialsException extends SerialisableException {

    public function __construct() {
        parent::__construct("The api key or secret supplied was invalid");
    }

}
