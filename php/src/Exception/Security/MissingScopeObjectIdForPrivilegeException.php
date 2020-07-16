<?php


namespace Kiniauth\Exception\Security;


/**
 * Missing scope object id for a privilege.  This is normally raised when a method
 * has been marked up with a @hasPrivilege tag but no qualifying data item.
 */
class MissingScopeObjectIdForPrivilegeException extends \Exception {


    public function __construct($privilege = null) {
        parent::__construct("The privilege $privilege has been requested for checking but no scope object id has been supplied.
        This probably means that you are checking a privilege of a type different to ACCOUNT.");
    }

}
