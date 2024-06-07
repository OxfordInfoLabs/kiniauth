<?php

namespace Kiniauth\Exception\Security;

class ObjectNotSharableException extends \Exception {

    public function __construct($objectClass) {
        parent::__construct("You have attempted to assign an object of type $objectClass which is not sharable");
    }

}