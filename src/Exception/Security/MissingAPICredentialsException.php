<?php


namespace Kiniauth\Exception\Security;


class MissingAPICredentialsException extends \Exception {

    public function __construct() {
        parent::__construct("The api key and/or secret have not been supplied correctly.");
    }

}
