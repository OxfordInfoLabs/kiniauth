<?php


namespace Kiniauth\Exception\Security;


use Kinikit\Core\Exception\SerialisableException;

class InvalidLoginException extends SerialisableException {

    public function __construct() {
        parent::__construct("The username or password supplied was invalid");
    }

}
