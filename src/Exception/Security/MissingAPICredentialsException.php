<?php


namespace Kiniauth\Exception\Security;


use Kinikit\Core\Exception\SerialisableException;

class MissingAPICredentialsException extends SerialisableException {

    public function __construct() {
        parent::__construct("The api key and/or secret have not been supplied correctly.");
    }

}
