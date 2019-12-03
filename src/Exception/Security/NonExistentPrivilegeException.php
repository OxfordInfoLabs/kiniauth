<?php


namespace Kiniauth\Exception\Security;


class NonExistentPrivilegeException extends \Exception {

    public function __construct($privilegeScope, $privilege) {
        parent::__construct("The $privilegeScope privilege $privilege does not exist");
    }

}
