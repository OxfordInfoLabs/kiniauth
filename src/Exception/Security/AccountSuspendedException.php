<?php


namespace Kiniauth\Exception\Security;


use Kinikit\Core\Exception\SerialisableException;

class AccountSuspendedException extends SerialisableException {

    public function __construct() {
        parent::__construct("Your account has been suspended.");
    }

}
