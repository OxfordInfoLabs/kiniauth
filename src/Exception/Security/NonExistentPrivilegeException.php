<?php


namespace Kiniauth\Exception\Security;


use Kinikit\Core\Exception\SerialisableException;

class NonExistentPrivilegeException extends SerialisableException {

    public function __construct($privilege = null) {
        parent::__construct("The privilege $privilege does not exist");
    }

}
