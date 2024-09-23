<?php

namespace Kiniauth\Exception\Security;

class InvalidIPAddressAPIKeyException extends \Exception {

    public function __construct() {
        parent::__construct("IP Address is not whitelisted");
    }

}