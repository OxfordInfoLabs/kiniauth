<?php


namespace Kiniauth\Exception\Security;


class MissingCSRFHeaderException extends \Exception {

    public function __construct() {
        parent::__construct("No CSRF token supplied for user authenticated request");
    }

}
