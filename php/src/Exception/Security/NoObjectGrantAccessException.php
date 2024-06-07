<?php

namespace Kiniauth\Exception\Security;

class NoObjectGrantAccessException extends \Exception {

    public function __construct($className, $primaryKey) {
        parent::__construct("The logged in user does not have grant access for object of type $className with primary key $primaryKey");
    }

}