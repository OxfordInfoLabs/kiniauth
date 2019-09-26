<?php


namespace Kiniauth\Exception\Security;


class NonExistentPrivilegeException extends \Exception {

    public function __construct($privilege = null) {
        parent::__construct("The privilege $privilege does not exist");
    }

}
