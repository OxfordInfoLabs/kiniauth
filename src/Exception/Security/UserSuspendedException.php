<?php


namespace Kiniauth\Exception\Security;


use Kinikit\Core\Exception\SerialisableException;

class UserSuspendedException extends SerialisableException {

    public function __construct() {
        parent::__construct("Your user account has been suspended.");
    }

}
